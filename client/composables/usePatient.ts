import { ref } from "vue";
import { employeeService } from "~/api/employee/EmployeeService";
import { medicationService } from "~/api/medication/MedicationService";
import { patientService } from "~/api/patient/PatientService";
import { scheduleService } from "~/api/schedule/ScheduleService";
import { serviceService } from "~/api/service/ServiceService";
import type { Employee } from "~/types/employee";
import type { MarkDosePayload, Medication, MedicationForm, MedicationSchedule, Vital, VitalFormData } from "~/types/medication";
import type { PatientRetrieve } from "~/types/patient";
import type { ScheduleItem } from "~/types/schedule";
import type { Service } from "~/types/service";

export function usePatient() {
    const patientData = ref<PatientRetrieve | null>(null);
    const serviceData = ref<Service[]>([]);
    const scheduleData = ref([]);
    const employeeData = ref<Employee[]>([]);
    const loading = ref(true);

    const medications = ref<Medication[]>([]);
    const vitals = ref<Vital[]>([]);

    async function fetchData(uuid: string, b_uuid: string) {
        try {
            loading.value = true;
            const patientRes = await patientService.show(uuid);
            patientData.value = patientRes.data[0];
            loading.value = false;
            const [serviceRes, scheduleRes] = await Promise.all([
                serviceService.list({
                    branch_uuid: b_uuid,
                    type: "facility",
                }),
                scheduleService.list({}),
            ]);
            serviceData.value = serviceRes.services ?? serviceRes.data ?? [];
            scheduleData.value = scheduleRes.data;

            loadMedications();
            loadVitals();
        } catch (error) {
            console.error(error);
            loading.value = false;
        }
    }

    async function fetchEmployee(b_uuid: string, schedule_id: number) {
        try {
            const employeeRes = await employeeService.list({
                schedule_id: schedule_id,
                branch_uuid: b_uuid,
                type: "schedule"
            });
            employeeData.value = employeeRes.data;
        } catch (error) {
            console.error(error);
        }
    }

    function loadMedications() {

        if (!patientData.value?.medication) {
            medications.value = [];
            return;
        }
        medications.value = patientData.value.medication.map((item: Medication) => {
            const kind =
                item.kind?.trim()?.toLowerCase() === "prn"
                    ? "PRN"
                    : "Scheduled";

            console.log()

            return {
                ...item,
                recorded_date: item.recorded_date ?? "",
                durationLabel: item.duration
                    ? `${item.duration} Days`
                    : "Ongoing",
                kind,
                times: item.times ?? [],
            };
        });
    }

    function loadVitals() {
        if (!patientData.value?.vital) {
            vitals.value = [];
            return;
        }
        return vitals.value;
    }

    function normalizeMedication(item: any): Medication {
        const kind =
            item.kind?.trim()?.toLowerCase() === "prn" ? "PRN" : "Scheduled";

        return {
            ...item,
            recorded_date: item.recorded_date ?? "",
            durationLabel: item.durationLabel
                ?? (item.duration === "ongoing" || !item.duration
                    ? "Ongoing"
                    : `${item.duration} Days`),
            kind,
            times: item.times ?? [],
            schedules: item.schedules ?? [],
        };
    }

    async function handleVitalAction(
        action: "create" | "update",
        payload: VitalFormData,
        p_uuid: string,
        id?: string,
    ) {
        try {
            if (action === "create") {
                const res = await medicationService.create({
                    category: "Vital Signs",
                    patient_uuid: p_uuid,
                    payload,
                });
                const item = res.data.data ?? res.data;
                vitals.value.push({
                    ...item,
                });
                return res;
            }

            if (action === "update" && id) {
                const index = vitals.value.findIndex((v) => v.id === id);
                if (index === -1) return;
                const res = await medicationService.update(id, {
                    patient_uuid: p_uuid,
                    payload,
                });
                const item = res.data;
                vitals.value[index] = {
                    ...vitals.value[index],
                    ...item,
                };
                return res;
            }
        } catch (err) {
            console.log(err);
        }
    }

    async function handleMedicationAction(
        action: "create" | "update",
        payload: MedicationForm,
        p_uuid: string,
        id?: string
    ) {
        try {
            if (action === "create") {
                const res = await medicationService.create({
                    category: "Medication",
                    patient_uuid: p_uuid,
                    payload,
                });
                const item = res.data.data ?? res.data;
                medications.value.push(normalizeMedication(item));
                return res;
            }

            if (action === "update" && id) {
                const index = vitals.value.findIndex((v) => v.id === id);
                if (index === -1) return;
                const res = await medicationService.update(id, {
                    patient_uuid: p_uuid,
                    payload,
                });
                const item = res.data;
                medications.value[index] = normalizeMedication({
                    ...medications.value[index],
                    ...item,
                });
                return res;
            }
        } catch (err) {
            throw err;
        }
    }

    async function handleDosageAction(
        payload: MarkDosePayload,
        p_uuid: string,
    ) {

        try {

            const res = await medicationService.create({
                category: "dosage",
                patient_uuid: p_uuid,
                medSchedule: payload,
            });

            const data = res.data ?? res;
            const medicationIndex = medications.value.findIndex(
                (med) => med.id === payload.medication_id,
            );
            const medication = medications.value[medicationIndex] as Medication;

            if (payload.status === "removed") {
                medication.schedules = medication.schedules?.filter(
                    (schedule: any) => schedule.id !== payload.schedule_id,
                ) ?? [];
            }

            if (payload.status === "taken") {
                medication.schedules ??= [];
                medication.schedules.push(data);
            }

            return res;
        } catch (err) {
            throw err;
        }
    }

    async function handleScheduleAction(payload: any, p_uuid: string, b_uuid: string) {
        try {
            const res = await scheduleService.create({
                branch_uuid: b_uuid,
                patient_uuid: p_uuid,
                ...payload.form,
                services: payload.services,
            });

            return res;
        } catch (err) {
            throw err;
        }
    }

    async function handleAssignment(payload: any, b_uuid: string) {
        try {
            const res = await scheduleService.assign({
                branch_uuid: b_uuid,
                ...payload
            });
            scheduleData.value = res.data;
            return res.data;
        } catch (err) {
            throw err;
        }
    }

    async function updateSchedule(payload: any, b_uuid: string) {
        try {
            const res = await scheduleService.update(payload.schedule_id, {
                branch_uuid: b_uuid,
                ...payload
            });
            scheduleData.value = res.data;
            return res.data;
        } catch (err) {
            throw err;
        }
    }

    return {
        patientData,
        serviceData,
        scheduleData,
        employeeData,
        loading,
        medications,
        vitals,
        updateSchedule,
        handleDosageAction,
        handleMedicationAction,
        handleVitalAction,
        handleScheduleAction,
        handleAssignment,
        fetchData,
        fetchEmployee,
    };
}
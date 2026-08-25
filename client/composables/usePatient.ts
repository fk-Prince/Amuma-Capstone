import { ref } from "vue";
import { employeeService } from "~/api/employee/EmployeeService";
import { medicationService } from "~/api/medication/MedicationService";
import { patientActivityService } from "~/api/patient-activity/PatientActivityService";
import { patientService } from "~/api/patient/PatientService";
import { scheduleService } from "~/api/schedule/ScheduleService";
import { serviceService } from "~/api/service/ServiceService";
import { vitalService } from "~/api/vital/VitalService";
import type { Employee } from "~/types/employee";
import type { MarkDosePayload, Medication, MedicationForm, Vital, VitalFormData } from "~/types/medication";
import type { PatientActivity, PatientActivityForm } from "~/types/patient-activity";
import type { PatientRetrieve } from "~/types/patient";
import type { ScheduleItem } from "~/types/schedule";
import type { Service } from "~/types/service";

export function usePatient() {
    const patientData = ref<PatientRetrieve | null>(null);
    const serviceData = ref<Service[]>([]);
    const scheduleData = ref<ScheduleItem[]>([]);
    const employeeData = ref<Employee[]>([]);
    const loading = ref(true);

    const medications = ref<Medication[]>([]);
    const vitals = ref<Vital[]>([]);
    const patientActivities = ref<PatientActivity[]>([]);
    const loadingSecond = ref(true);

    interface PageMeta {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    }

    const medicationsMeta = ref<PageMeta | null>(null);
    const vitalsMeta = ref<PageMeta | null>(null);
    const patientActivitiesMeta = ref<PageMeta | null>(null);
    const isFetchingMedications = ref(false);
    const isFetchingVitals = ref(false);
    const isFetchingPatientActivities = ref(false);

    async function fetchData(uuid: string, b_uuid: string) {
        try {
            loading.value = true;
            loadingSecond.value = true;
            const patientRes = await patientService.show({
                branch_uuid: b_uuid,
            }, uuid);
            patientData.value = patientRes.data;
            loading.value = false;
            const [serviceRes] = await Promise.all([
                serviceService.list({
                    branch_uuid: b_uuid,
                    type: "facility",
                }),
            ]);
            serviceData.value = serviceRes.services ?? serviceRes.data ?? [];
        } catch (error) {
            console.error(error);
            loading.value = false;
            loadingSecond.value = false;
        }
    }

    async function fetchMedications(p_uuid: string, page = 1) {
        isFetchingMedications.value = true;
        try {
            const res = await medicationService.list({
                patient_uuid: p_uuid,
                page,
            });
            medications.value = (res.data ?? []).map((item: any) =>
                normalizeMedication(item),
            );
            medicationsMeta.value = res.meta ?? null;
        } catch (error) {
            console.error(error);
        } finally {
            isFetchingMedications.value = false;
        }
    }

    async function fetchVitals(p_uuid: string, page = 1) {
        isFetchingVitals.value = true;
        try {
            const res = await vitalService.list({
                patient_uuid: p_uuid,
                page,
            });
            vitals.value = res.data ?? [];
            vitalsMeta.value = res.meta ?? null;
        } catch (error) {
            console.error(error);
        } finally {
            isFetchingVitals.value = false;
        }
    }

    async function fetchPatientActivities(p_uuid: string, page = 1) {
        isFetchingPatientActivities.value = true;
        try {
            const res = await patientActivityService.list({
                patient_uuid: p_uuid,
                page,
            });
            patientActivities.value = res.data ?? [];
            patientActivitiesMeta.value = res.meta ?? null;
        } catch (error) {
            console.error(error);
        } finally {
            isFetchingPatientActivities.value = false;
        }
    }

    async function fetchSchedules(
        p_uuid: string,
        b_uuid: string,
        from?: string,
        to?: string,
    ) {
        try {
            const scheduleRes = await scheduleService.list({
                branch_uuid: b_uuid,
                patient_uuid: p_uuid,
                ...(from && { date_from: from }),
                ...(to && { date_to: to }),
            });

            scheduleData.value = scheduleRes.data?.data ?? scheduleRes.data ?? [];
        } catch (error) {
            console.error(error);
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
                const res = await vitalService.create({
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
                const res = await vitalService.update(id, {
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
                    patient_uuid: p_uuid,
                    payload,
                });
                const item = res.data.data ?? res.data;
                const medication = normalizeMedication(item);
                medications.value.push(medication);
                return res;
            }

            if (action === "update" && id) {
                const index = medications.value.findIndex((m) => m.id === id);
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
            const res = await medicationService.dosage({
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

    async function handlePatientActivityAction(
        action: "create" | "update",
        payload: PatientActivityForm,
        p_uuid: string,
        id?: string,
    ) {
        try {
            if (action === "create") {
                const res = await patientActivityService.create({
                    patient_uuid: p_uuid,
                    payload,
                });
                const item = res.data.data ?? res.data;
                patientActivities.value.unshift(item);
                return res;
            }

            if (action === "update" && id) {
                const index = patientActivities.value.findIndex(
                    (a) => a.id === id,
                );
                if (index === -1) return;
                const res = await patientActivityService.update(id, {
                    patient_uuid: p_uuid,
                    payload,
                });
                const item = res.data;
                patientActivities.value[index] = {
                    ...patientActivities.value[index],
                    ...item,
                };
                return res;
            }
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
            const res = await scheduleService.action({
                type: "assign",
                branch_uuid: b_uuid,
                ...payload
            });
            const updated = res.data;
            const index = scheduleData.value.findIndex(
                (s) => s.schedule_id === updated.schedule_id,
            );

            if (index !== -1) {
                scheduleData.value[index] = updated;
            }
            return res;
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


            return res;
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
        loadingSecond,
        medications,
        vitals,
        patientActivities,
        medicationsMeta,
        vitalsMeta,
        patientActivitiesMeta,
        isFetchingMedications,
        isFetchingVitals,
        isFetchingPatientActivities,
        updateSchedule,
        handleDosageAction,
        handleMedicationAction,
        handleVitalAction,
        handlePatientActivityAction,
        handleScheduleAction,
        handleAssignment,
        fetchData,
        fetchMedications,
        fetchVitals,
        fetchPatientActivities,
        fetchEmployee,
        fetchSchedules
    };
}
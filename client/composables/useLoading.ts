// import { ref, readonly } from "vue";

// const isFetching = ref(false);
// let count = 0;

// export const useLoadingIndicator = () => {
//     const start = () => {
//         count++;
//         isFetching.value = true;
//     };

//     const finish = () => {
//         count = Math.max(0, count - 1);
//         if (count === 0) isFetching.value = false;
//     };

//     return {
//         isFetching: readonly(isFetching),
//         start,
//         finish,
//     };
// };
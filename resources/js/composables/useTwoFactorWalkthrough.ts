import { ref } from 'vue';

const isSweetAlertOpen = ref<boolean>(false);
const isTourOpen = ref<boolean>(false);
const isModalPaused = ref<boolean>(false);
const tourStep = ref<number>(0); // 0: Tab Keamanan, 1: Tombol Aktifkan 2FA, 2: Pindai Barcode & Token

export function useTwoFactorWalkthrough() {
    const openSweetAlert = () => {
        isSweetAlertOpen.value = true;
        isTourOpen.value = false;
    };

    const closeSweetAlert = () => {
        isSweetAlertOpen.value = false;
        isTourOpen.value = false;
        isModalPaused.value = false;
    };

    const startTour = () => {
        isSweetAlertOpen.value = false;
        isModalPaused.value = false;

        // If user is already on the Security tab, start directly at Step 1
        if (
            typeof window !== 'undefined' &&
            window.location.pathname.startsWith('/settings/security')
        ) {
            tourStep.value = 1;
        } else {
            tourStep.value = 0;
        }

        isTourOpen.value = true;
    };

    const pauseTourForModal = () => {
        isModalPaused.value = true;
    };

    const resumeTourFromModal = () => {
        isModalPaused.value = false;
    };

    const nextTourStep = () => {
        if (tourStep.value < 2) {
            tourStep.value += 1;
        } else {
            isTourOpen.value = false;
        }
    };

    const prevTourStep = () => {
        if (tourStep.value > 0) {
            tourStep.value -= 1;
        }
    };

    const stopTour = () => {
        isTourOpen.value = false;
        isModalPaused.value = false;
    };

    return {
        isSweetAlertOpen,
        isTourOpen,
        isModalPaused,
        tourStep,
        openSweetAlert,
        closeSweetAlert,
        startTour,
        pauseTourForModal,
        resumeTourFromModal,
        nextTourStep,
        prevTourStep,
        stopTour,
    };
}

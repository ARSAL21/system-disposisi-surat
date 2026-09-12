<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ChevronRight, X } from '@lucide/vue';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { useTwoFactorWalkthrough } from '@/composables/useTwoFactorWalkthrough';

const page = usePage();
const { isTourOpen, isModalPaused, tourStep, nextTourStep, stopTour } =
    useTwoFactorWalkthrough();

const popoverRef = ref<HTMLElement | null>(null);
const coords = ref<{
    top: number;
    left: number;
    arrowLeft: number;
    placement: 'top' | 'bottom';
}>({
    top: 0,
    left: 0,
    arrowLeft: 24,
    placement: 'top',
});

const isAnyModalOpen = ref(false);

let checkModalRaf: number | null = null;

const checkOtherModals = () => {
    if (typeof window === 'undefined') {
        return;
    }

    if (checkModalRaf !== null) {
        cancelAnimationFrame(checkModalRaf);
    }

    checkModalRaf = requestAnimationFrame(() => {
        checkModalRaf = null;

        const dialogs = document.querySelectorAll(
            '[role="dialog"], [role="alertdialog"]',
        );
        let hasOpenDialog = false;

        dialogs.forEach((el) => {
            if (
                el.id !== 'two-factor-tour-popover' &&
                !el.closest('#two-factor-tour-popover') &&
                el.isConnected
            ) {
                // If it is closed or transitioning out, do not count as open
                if (el.getAttribute('data-state') === 'closed') {
                    return;
                }

                try {
                    const style = window.getComputedStyle(el);

                    if (
                        style.display !== 'none' &&
                        style.visibility !== 'hidden' &&
                        style.opacity !== '0'
                    ) {
                        hasOpenDialog = true;
                    }
                } catch {
                    // Element might be detached mid-frame
                }
            }
        });

        // Check for active modal backdrops or portals with open state
        const openPortals = document.querySelectorAll(
            '[data-state="open"][data-slot*="dialog"], [data-state="open"][data-slot*="modal"]',
        );

        if (openPortals.length > 0) {
            hasOpenDialog = true;
        }

        isAnyModalOpen.value = hasOpenDialog;
    });
};

const steps = [
    {
        targetId: 'tab-security',
        title: 'Buka Tab Keamanan',
        description:
            'Masukkan password akun terlebih dahulu untuk membuka menu Keamanan dan memulai konfigurasi 2FA.',
        btnText: 'Lanjut',
    },
    {
        targetId: 'two-factor-setup-card',
        title: 'Mulai Konfigurasi 2FA',
        description:
            'Tekan tombol "Aktifkan 2FA Sekarang" pada kartu ini untuk menampilkan kode QR otentikasi akun Anda.',
        btnText: 'Lanjut',
    },
    {
        targetId: 'two-factor-setup-card',
        title: 'Pindai Barcode & Konfirmasi',
        description:
            'Buka aplikasi Google Authenticator atau Microsoft Authenticator di ponsel, pindai QR code yang tampil, lalu masukkan 6 digit token untuk membuka seluruh akses sistem.',
        btnText: 'Selesai & Paham',
    },
];

let previousTargetEl: HTMLElement | null = null;
let modalObserver: MutationObserver | null = null;

const removeTargetHighlight = () => {
    if (previousTargetEl) {
        previousTargetEl.classList.remove(
            'ring-4',
            'ring-sky-400',
            'ring-offset-2',
            'ring-offset-background',
            'transition-all',
            'duration-300',
        );
        previousTargetEl = null;
    }
};

const updatePosition = () => {
    if (!isTourOpen.value || isModalPaused.value || isAnyModalOpen.value) {
        removeTargetHighlight();

        return;
    }

    const currentStepConfig = steps[tourStep.value];

    if (!currentStepConfig) {
        removeTargetHighlight();

        return;
    }

    const targetEl = document.getElementById(currentStepConfig.targetId);

    if (!targetEl) {
        removeTargetHighlight();

        return;
    }

    removeTargetHighlight();

    // Highlight target element with glowing ring
    targetEl.classList.add(
        'ring-4',
        'ring-sky-400',
        'ring-offset-2',
        'ring-offset-background',
        'transition-all',
        'duration-300',
    );
    previousTargetEl = targetEl;

    const rect = targetEl.getBoundingClientRect();
    const popoverWidth = popoverRef.value?.offsetWidth || 340;
    const popoverHeight = popoverRef.value?.offsetHeight || 160;

    // Calculate horizontal center
    const targetCenterX = rect.left + rect.width / 2;
    let left = targetCenterX - popoverWidth / 2;
    left = Math.max(16, Math.min(window.innerWidth - popoverWidth - 16, left));

    const arrowLeft = Math.max(
        20,
        Math.min(popoverWidth - 28, targetCenterX - left),
    );

    // Determine whether to place above or below relative to viewport (fixed positioning)
    let placement: 'top' | 'bottom' = 'top';
    let top = rect.top - popoverHeight - 14;

    if (rect.top < popoverHeight + 20) {
        // Not enough room on top, place below
        placement = 'bottom';
        top = rect.bottom + 14;
    }

    coords.value = {
        top: Math.round(top),
        left: Math.round(left),
        arrowLeft: Math.round(arrowLeft),
        placement,
    };
};

const handleNext = () => {
    if (tourStep.value === 0) {
        // If already on security page, advance immediately
        if (window.location.pathname.startsWith('/settings/security')) {
            tourStep.value = 1;
            nextTick(() => {
                updatePosition();
                const cardEl = document.getElementById('two-factor-setup-card');

                if (cardEl) {
                    cardEl.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
                }
            });

            return;
        }

        // Trigger click on target tab if it exists
        const tabEl = document.getElementById('tab-security');

        if (tabEl) {
            tabEl.click();
        } else {
            router.visit('/settings/security', {
                preserveScroll: true,
                onSuccess: () => {
                    tourStep.value = 1;
                    nextTick(() => {
                        updatePosition();
                    });
                },
            });
        }

        // DO NOT advance tourStep to 1 while on profile page.
        // The click will either open ConfirmPasswordModal (which pauses/hides the popover)
        // or navigate to /settings/security (which will automatically advance tourStep to 1).
    } else if (tourStep.value === 1) {
        const actionBtn = document.getElementById('two-factor-action-button');

        if (actionBtn) {
            actionBtn.click();
        }

        nextTourStep();
        setTimeout(() => updatePosition(), 200);
    } else {
        stopTour();
        removeTargetHighlight();
    }
};

const handleClose = () => {
    stopTour();
    removeTargetHighlight();
};

watch(
    () => [
        isTourOpen.value,
        tourStep.value,
        isModalPaused.value,
        isAnyModalOpen.value,
    ],
    () => {
        if (isTourOpen.value && !isModalPaused.value && !isAnyModalOpen.value) {
            nextTick(() => {
                updatePosition();
                setTimeout(updatePosition, 100);
            });
        } else {
            removeTargetHighlight();
        }
    },
);

watch(
    () => page.url,
    (newUrl) => {
        if (!isTourOpen.value) {
            return;
        }

        // Auto transition tour step based on route
        if (
            newUrl.startsWith('/settings/security') ||
            window.location.pathname.startsWith('/settings/security')
        ) {
            if (tourStep.value === 0) {
                tourStep.value = 1;
            }
        } else if (
            newUrl.startsWith('/settings/profile') ||
            window.location.pathname.startsWith('/settings/profile')
        ) {
            if (tourStep.value > 0) {
                tourStep.value = 0;
            }
        }

        setTimeout(() => {
            checkOtherModals();
            updatePosition();
        }, 250);
    },
);

onMounted(() => {
    window.addEventListener('resize', updatePosition);
    window.addEventListener('scroll', updatePosition, { passive: true });

    modalObserver = new MutationObserver(() => {
        checkOtherModals();

        if (isTourOpen.value && !isModalPaused.value && !isAnyModalOpen.value) {
            nextTick(() => updatePosition());
        }
    });

    modalObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });

    checkOtherModals();

    if (
        window.location.pathname.startsWith('/settings/security') &&
        tourStep.value === 0
    ) {
        tourStep.value = 1;
    }

    if (isTourOpen.value && !isModalPaused.value && !isAnyModalOpen.value) {
        nextTick(() => {
            updatePosition();
        });
    }
});

onUnmounted(() => {
    removeTargetHighlight();
    window.removeEventListener('resize', updatePosition);
    window.removeEventListener('scroll', updatePosition);

    if (checkModalRaf !== null) {
        cancelAnimationFrame(checkModalRaf);
        checkModalRaf = null;
    }

    if (modalObserver) {
        modalObserver.disconnect();
        modalObserver = null;
    }
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isTourOpen && !isModalPaused && !isAnyModalOpen"
            id="two-factor-tour-popover"
            ref="popoverRef"
            class="pointer-events-auto fixed z-[9999] w-[320px] rounded-2xl bg-[#0284c7] p-5 text-white shadow-2xl transition-all duration-200 ease-out sm:w-[340px]"
            :style="{
                top: `${coords.top}px`,
                left: `${coords.left}px`,
            }"
            role="region"
            aria-label="Panduan Aktivasi 2FA"
        >
            <!-- Downward Arrow (when placement is top) -->
            <div
                v-if="coords.placement === 'top'"
                class="absolute -bottom-2.5 h-0 w-0 border-x-8 border-t-[10px] border-x-transparent border-t-[#0284c7]"
                :style="{ left: `${coords.arrowLeft - 8}px` }"
            />

            <!-- Upward Arrow (when placement is bottom) -->
            <div
                v-if="coords.placement === 'bottom'"
                class="absolute -top-2.5 h-0 w-0 border-x-8 border-b-[10px] border-x-transparent border-b-[#0284c7]"
                :style="{ left: `${coords.arrowLeft - 8}px` }"
            />

            <!-- Header & Close -->
            <div class="flex items-start justify-between gap-2">
                <h3 class="text-base font-bold tracking-tight text-white">
                    {{ steps[tourStep]?.title }}
                </h3>
                <button
                    type="button"
                    class="-mt-1.5 -mr-1.5 flex size-6 items-center justify-center rounded-full text-sky-100 opacity-70 transition-opacity hover:bg-sky-700/50 hover:opacity-100"
                    @click="handleClose"
                >
                    <X class="size-4" />
                    <span class="sr-only">Tutup panduan</span>
                </button>
            </div>

            <!-- Description -->
            <p class="mt-2 text-xs leading-relaxed text-sky-50">
                {{ steps[tourStep]?.description }}
            </p>

            <!-- Bottom Navigation Bar: Dots & Next Button -->
            <div class="mt-5 flex items-center justify-between">
                <!-- 3 Step Dots -->
                <div class="flex items-center gap-1.5">
                    <span
                        v-for="(s, index) in steps"
                        :key="index"
                        class="size-2 rounded-full transition-all duration-300"
                        :class="
                            tourStep === index
                                ? 'w-4 bg-white'
                                : 'bg-sky-300/60'
                        "
                    />
                </div>

                <!-- Next Button -->
                <button
                    type="button"
                    class="flex items-center gap-1 rounded-lg bg-slate-950 px-4 py-1.5 text-xs font-bold text-sky-400 shadow-md transition-colors hover:bg-slate-900 hover:text-sky-300 focus:ring-2 focus:ring-slate-950 focus:ring-offset-2 focus:ring-offset-[#0284c7] focus:outline-none"
                    @click="handleNext"
                >
                    <span>{{ steps[tourStep]?.btnText }}</span>
                    <ChevronRight
                        v-if="tourStep < steps.length - 1"
                        class="size-3.5"
                    />
                </button>
            </div>
        </div>
    </Teleport>
</template>

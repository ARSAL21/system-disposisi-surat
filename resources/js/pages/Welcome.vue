<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LandingBentoGrid from '@/components/landing/LandingBentoGrid.vue';
import LandingCtaFooter from '@/components/landing/LandingCtaFooter.vue';
import LandingHeroSection from '@/components/landing/LandingHeroSection.vue';
import LandingNavbar from '@/components/landing/LandingNavbar.vue';
import LandingPublicTracker from '@/components/landing/LandingPublicTracker.vue';
import LandingWorkflowSimulator from '@/components/landing/LandingWorkflowSimulator.vue';
</script>

<template>
    <Head title="Sistem Disposisi & Tata Naskah Dinas Terintegrasi">
        <!-- Google Fonts: Syne (Display), Plus Jakarta Sans (Body), JetBrains Mono (Cryptographic Hashes) -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Syne:wght@600;700;800&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="min-h-screen bg-[#fafbfc] text-[#0f172a] antialiased selection:bg-indigo-600 selection:text-white dark:bg-[#080b14] dark:text-[#f8fafc]">
        <!-- Ambient Radial Background Gradients -->
        <div
            aria-hidden="true"
            class="pointer-events-none fixed inset-0 z-0 overflow-hidden"
        >
            <div
                class="absolute -top-[20%] left-1/2 h-[750px] w-[950px] -translate-x-1/2 rounded-full bg-gradient-to-tr from-indigo-500/15 via-sky-400/10 to-teal-400/10 blur-[130px] dark:from-indigo-600/20 dark:via-purple-600/15 dark:to-teal-500/10"
            />
            <div
                class="absolute top-[45%] -left-[15%] h-[600px] w-[700px] rounded-full bg-gradient-to-br from-indigo-400/10 to-transparent blur-[120px] dark:from-indigo-900/15"
            />
            <div
                class="absolute top-[70%] -right-[15%] h-[650px] w-[750px] rounded-full bg-gradient-to-tl from-teal-400/10 via-indigo-500/10 to-transparent blur-[130px] dark:from-teal-900/15 dark:via-indigo-950/20"
            />
            <div
                class="absolute inset-0 [background-image:radial-gradient(#94a3b8_1px,transparent_1px)] [background-size:32px_32px] opacity-[0.18] dark:[background-image:radial-gradient(#334155_1px,transparent_1px)] dark:opacity-[0.25]"
            />
        </div>

        <main class="relative z-10 w-full max-w-full overflow-x-hidden">
            <!-- 1. Floating Pill Navigation -->
            <LandingNavbar />

            <!-- 2. Attention: Hero Section with Ultra-Wide H1 & Action CTAs -->
            <LandingHeroSection />

            <!-- 3. Interest: Dense Interlocking Bento Grid -->
            <LandingBentoGrid />

            <!-- 4. Desire: Interactive 4-Tier Workflow Simulator Sandbox -->
            <LandingWorkflowSimulator />

            <!-- 5. Utility & Trust: Live Public Letter Tracking Wayfinder -->
            <LandingPublicTracker />

            <!-- 6. Action: Final Civic CTA & Municipal Footer -->
            <LandingCtaFooter />
        </main>
    </div>
</template>

<style scoped>
/* Syne display font smoothing */
:deep(h1), :deep(h2), :deep(h3), :deep(h4) {
    font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
}
</style>

import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Ref } from 'vue';
import { toast } from 'vue-sonner';
import type {
    CreateDocumentVersionPayload,
    DocumentVersionCapabilities,
    DocumentVersionHistoryResponse,
    DocumentVersionItem,
    DocumentVersionLetter,
} from '@/types';

export function useDocumentVersions(
    initialData: DocumentVersionHistoryResponse,
    previewMode: Ref<boolean>,
    storeRoute: Ref<string | null>,
) {
    const letter = ref<DocumentVersionLetter>({ ...initialData.letter });
    const versions = ref<DocumentVersionItem[]>([...initialData.versions]);
    const capabilities = ref<DocumentVersionCapabilities>({
        ...initialData.capabilities,
    });
    const nextVersionNumber = ref<number>(initialData.next_version_number);

    const selectedDetailVersion = ref<DocumentVersionItem | null>(null);
    const isDetailDialogOpen = ref<boolean>(false);
    const isCreateDialogOpen = ref<boolean>(false);
    const isUploading = ref<boolean>(false);
    const createErrors = ref<Record<string, string>>({});

    function openDetail(version: DocumentVersionItem): void {
        selectedDetailVersion.value = version;
        isDetailDialogOpen.value = true;
    }

    function closeDetail(): void {
        isDetailDialogOpen.value = false;
        selectedDetailVersion.value = null;
    }

    function openCreate(): void {
        createErrors.value = {};
        isCreateDialogOpen.value = true;
    }

    function closeCreate(): void {
        isCreateDialogOpen.value = false;
        createErrors.value = {};
    }

    async function copyHash(hash: string): Promise<void> {
        try {
            if (navigator?.clipboard?.writeText) {
                await navigator.clipboard.writeText(hash);
                toast.success(
                    'Sidik jari SHA-256 berhasil disalin ke papan klip.',
                );
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = hash;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                toast.success(
                    'Sidik jari SHA-256 berhasil disalin ke papan klip.',
                );
            }
        } catch {
            toast.error('Gagal menyalin sidik jari ke papan klip.');
        }
    }

    function submitCreate(payload: CreateDocumentVersionPayload): void {
        createErrors.value = {};

        if (!payload.document) {
            createErrors.value.document = 'Berkas PDF wajib diunggah.';

            return;
        }

        if (
            !payload.correction_reason ||
            payload.correction_reason.trim().length < 10
        ) {
            createErrors.value.correction_reason =
                'Alasan koreksi wajib diisi minimal 10 karakter.';

            return;
        }

        if (payload.correction_reason.trim().length > 2000) {
            createErrors.value.correction_reason =
                'Alasan koreksi maksimal 2000 karakter.';

            return;
        }

        if (previewMode.value) {
            // Apply mock version update in preview mode
            const newVersionNum = nextVersionNumber.value;
            const updatedOldVersions = versions.value.map((v) => ({
                ...v,
                is_current: false,
            }));

            const newVersionItem: DocumentVersionItem = {
                id: Date.now(),
                version_number: newVersionNum,
                is_current: true,
                replaces_version_number: newVersionNum - 1,
                source: 'MANUAL_CORRECTION',
                original_filename: payload.document.name,
                mime_type: 'application/pdf',
                size_bytes: payload.document.size,
                sha256: 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                correction_reason: payload.correction_reason.trim(),
                uploaded_by: {
                    id: 2,
                    name: 'Drs. H. Ahmad Fauzi, M.Si.',
                    position: 'Kepala Bagian Umum',
                    unit: 'Bagian Umum',
                },
                recorded_by: {
                    id: 2,
                    name: 'Drs. H. Ahmad Fauzi, M.Si.',
                    position: 'Kepala Bagian Umum',
                    unit: 'Bagian Umum',
                },
                created_at: new Date().toISOString(),
                preview_url: `#preview-v${newVersionNum}`,
                download_url: `#download-v${newVersionNum}`,
            };

            versions.value = [newVersionItem, ...updatedOldVersions];
            nextVersionNumber.value = newVersionNum + 1;

            closeCreate();
            toast.success(
                `Versi dokumen baru (v${newVersionNum}) berhasil dibuat (mode pratinjau).`,
            );

            return;
        }

        if (!storeRoute.value) {
            toast.error('Endpoint penyimpanan versi dokumen tidak tersedia.');

            return;
        }

        // Inertia follows the server redirect back to the history page, so all
        // version props are refreshed from the authoritative backend response.
        const formData = new FormData();
        formData.append('document', payload.document);
        formData.append('correction_reason', payload.correction_reason.trim());

        router.post(storeRoute.value, formData, {
            preserveScroll: true,
            onStart: () => {
                isUploading.value = true;
            },
            onSuccess: () => {
                isUploading.value = false;
                closeCreate();
                toast.success('Versi dokumen resmi baru berhasil dibuat.');
            },
            onError: (errs) => {
                isUploading.value = false;
                createErrors.value = errs;
                toast.error(
                    'Gagal membuat versi dokumen baru. Periksa isian form.',
                );
            },
            onFinish: () => {
                isUploading.value = false;
            },
        });
    }

    return {
        letter,
        versions,
        capabilities,
        nextVersionNumber,
        selectedDetailVersion,
        isDetailDialogOpen,
        isCreateDialogOpen,
        isUploading,
        createErrors,
        openDetail,
        closeDetail,
        openCreate,
        closeCreate,
        copyHash,
        submitCreate,
    };
}

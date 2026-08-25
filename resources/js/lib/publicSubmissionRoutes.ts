export const publicSubmissionRoutes = {
    dashboard: '/public/dashboard',
    index: '/public/submissions',
    create: '/public/submissions/create',
    store: '/public/submissions',
    show: (publicId: string) =>
        `/public/submissions/${encodeURIComponent(publicId)}`,
    edit: (publicId: string) =>
        `/public/submissions/${encodeURIComponent(publicId)}/edit`,
    update: (publicId: string) =>
        `/public/submissions/${encodeURIComponent(publicId)}`,
    destroy: (publicId: string) =>
        `/public/submissions/${encodeURIComponent(publicId)}`,
    submit: (publicId: string) =>
        `/public/submissions/${encodeURIComponent(publicId)}/submit`,
    document: (publicId: string) =>
        `/public/submissions/${encodeURIComponent(publicId)}/document`,
};

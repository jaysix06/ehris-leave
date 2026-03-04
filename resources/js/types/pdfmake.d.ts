declare module 'pdfmake' {
    const pdfMake: {
        vfs?: Record<string, string>;
        [key: string]: unknown;
    };
    export default pdfMake;
}

declare module 'pdfmake/build/vfs_fonts' {
    export const pdfMake: { vfs?: Record<string, string> };
}

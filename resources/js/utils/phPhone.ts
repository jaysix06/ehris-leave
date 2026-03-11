export function digitsOnly(value: unknown): string {
    return String(value ?? '').replace(/\D+/g, '');
}

export function formatPhilippineMobile(value: unknown): string {
    const digits = digitsOnly(value).slice(0, 11);
    if (digits.length <= 4) {
        return digits;
    }
    if (digits.length <= 7) {
        return `${digits.slice(0, 4)}-${digits.slice(4)}`;
    }
    return `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
}

export function formatPhilippineLandline(value: unknown): string {
    const digits = digitsOnly(value).slice(0, 10);
    if (digits === '') {
        return '';
    }

    if (digits[0] !== '0') {
        if (digits.length <= 3) {
            return digits;
        }
        if (digits.length <= 7) {
            return `${digits.slice(0, 3)}-${digits.slice(3)}`;
        }
        return `${digits.slice(0, 4)}-${digits.slice(4)}`;
    }

    if (digits.startsWith('02')) {
        const rest = digits.slice(2);
        if (rest === '') {
            return '02';
        }
        if (rest.length <= 4) {
            return `02-${rest}`;
        }
        return `02-${rest.slice(0, 4)}-${rest.slice(4)}`;
    }

    if (digits.length <= 3) {
        return digits;
    }

    const area = digits.slice(0, 3);
    const rest = digits.slice(3);
    if (rest === '') {
        return area;
    }
    if (rest.length <= 3) {
        return `${area}-${rest}`;
    }
    return `${area}-${rest.slice(0, 3)}-${rest.slice(3)}`;
}


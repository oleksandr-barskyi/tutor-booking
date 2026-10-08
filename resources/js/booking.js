export function nextDays(from, count) {
    const days = [];
    for (let i = 0; i < count; i++) {
        const d = new Date(Date.UTC(from.getUTCFullYear(), from.getUTCMonth(), from.getUTCDate() + i));
        days.push(d.toISOString().slice(0, 10));
    }
    return days;
}

export function dayLabel(isoDate) {
    const d = new Date(`${isoDate}T12:00:00Z`);
    return d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', timeZone: 'UTC' });
}

export function splitByPartOfDay(slots) {
    const groups = { Morning: [], Afternoon: [], Evening: [] };
    for (const slot of slots) {
        const hour = Number(slot.local_time.slice(0, 2));
        const key = hour < 12 ? 'Morning' : hour < 17 ? 'Afternoon' : 'Evening';
        groups[key].push(slot);
    }
    return Object.entries(groups).filter(([, list]) => list.length > 0);
}

export function validateBooking({ name, email }) {
    const errors = {};
    if (name.trim().length < 2) errors.name = 'Enter your name';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) errors.email = 'Enter a valid email';
    return errors;
}

export function formatRate(uah) {
    return `${new Intl.NumberFormat('uk-UA').format(uah)} UAH / hour`;
}

export function apiError(status, body) {
    if (status === 409) return body?.message ?? 'This time was just taken. Pick another one.';
    if (status === 422 && body?.errors) return Object.values(body.errors).flat().join(' ');
    return body?.message ?? 'Something went wrong. Try again.';
}

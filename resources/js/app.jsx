import { StrictMode, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { apiError, dayLabel, formatRate, nextDays, splitByPartOfDay, validateBooking } from './booking';

async function request(url, options = {}) {
    const res = await fetch(url, {
        ...options,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...options.headers },
    });
    const body = await res.json().catch(() => null);
    if (!res.ok) throw new Error(apiError(res.status, body));
    return body;
}

function TutorPicker({ tutors, selected, onSelect }) {
    return (
        <div className="grid gap-3 sm:grid-cols-3">
            {tutors.map((t) => (
                <button
                    key={t.id}
                    type="button"
                    onClick={() => onSelect(t)}
                    className={`rounded-xl border p-4 text-left transition ${selected?.id === t.id ? 'border-indigo-600 ring-2 ring-indigo-200' : 'border-slate-200 bg-white hover:border-slate-300'}`}
                >
                    <div className="font-semibold">{t.name}</div>
                    <div className="text-sm text-slate-500">{t.subjects.join(', ')}</div>
                    <div className="mt-1 text-sm text-slate-700">{formatRate(t.hourly_rate)}</div>
                </button>
            ))}
        </div>
    );
}

function SlotGrid({ slots, chosen, onChoose }) {
    if (slots.length === 0) return <p className="text-slate-500">No free time on this day.</p>;
    return splitByPartOfDay(slots).map(([part, list]) => (
        <div key={part} className="mb-3">
            <div className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{part}</div>
            <div className="flex flex-wrap gap-2">
                {list.map((s) => (
                    <button
                        key={s.starts_at}
                        type="button"
                        onClick={() => onChoose(s)}
                        className={`rounded-lg border px-3 py-1.5 text-sm ${chosen?.starts_at === s.starts_at ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white hover:border-indigo-400'}`}
                    >
                        {s.local_time}
                    </button>
                ))}
            </div>
        </div>
    ));
}

function App() {
    const days = useMemo(() => nextDays(new Date(), 7), []);
    const [tutors, setTutors] = useState([]);
    const [tutor, setTutor] = useState(null);
    const [subject, setSubject] = useState('');
    const [date, setDate] = useState(days[1]);
    const [slots, setSlots] = useState([]);
    const [slot, setSlot] = useState(null);
    const [form, setForm] = useState({ name: '', email: '' });
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState(null);
    const [booking, setBooking] = useState(null);
    const [refresh, setRefresh] = useState(0);

    useEffect(() => {
        request('/api/tutors').then((r) => setTutors(r.data)).catch((e) => setMessage({ kind: 'error', text: e.message }));
    }, []);

    useEffect(() => {
        if (!tutor) return;
        setSlot(null);
        request(`/api/tutors/${tutor.id}/slots?date=${date}`)
            .then((r) => setSlots(r.slots))
            .catch((e) => setMessage({ kind: 'error', text: e.message }));
    }, [tutor, date, refresh]);

    function chooseTutor(t) {
        setTutor(t);
        setSubject(t.subjects[0]);
        setBooking(null);
        setMessage(null);
    }

    async function submit(e) {
        e.preventDefault();
        const found = validateBooking(form);
        setErrors(found);
        if (Object.keys(found).length > 0 || !slot) return;
        try {
            const r = await request('/api/bookings', {
                method: 'POST',
                body: JSON.stringify({
                    tutor_id: tutor.id,
                    student_name: form.name,
                    student_email: form.email,
                    subject,
                    starts_at: slot.starts_at,
                }),
            });
            setBooking(r.data);
            setMessage({ kind: 'ok', text: `Booked ${subject} with ${tutor.name} on ${dayLabel(date)} at ${slot.local_time}.` });
        } catch (err) {
            setMessage({ kind: 'error', text: err.message });
        }
        setRefresh((n) => n + 1);
    }

    async function cancel() {
        try {
            await request(`/api/bookings/${booking.id}?token=${booking.cancel_token}`, { method: 'DELETE' });
            setBooking(null);
            setMessage({ kind: 'ok', text: 'Lesson cancelled. The slot is free again.' });
        } catch (err) {
            setMessage({ kind: 'error', text: err.message });
        }
        setRefresh((n) => n + 1);
    }

    return (
        <main className="mx-auto flex max-w-4xl flex-col gap-6 px-4 py-10">
            <header>
                <p className="text-sm font-semibold uppercase tracking-widest text-indigo-600">Tutor Booking</p>
                <h1 className="text-3xl font-bold">Book an online lesson</h1>
            </header>

            <TutorPicker tutors={tutors} selected={tutor} onSelect={chooseTutor} />

            {tutor && (
                <section className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                    <div className="flex flex-wrap gap-2">
                        {days.map((d) => (
                            <button
                                key={d}
                                type="button"
                                onClick={() => setDate(d)}
                                className={`rounded-lg px-3 py-1.5 text-sm ${d === date ? 'bg-slate-900 text-white' : 'bg-slate-100'}`}
                            >
                                {dayLabel(d)}
                            </button>
                        ))}
                    </div>

                    <SlotGrid slots={slots} chosen={slot} onChoose={setSlot} />

                    {slot && !booking && (
                        <form onSubmit={submit} className="grid gap-3 sm:grid-cols-4" noValidate>
                            <select value={subject} onChange={(e) => setSubject(e.target.value)} className="rounded-lg border border-slate-200 px-3 py-2">
                                {tutor.subjects.map((s) => (
                                    <option key={s}>{s}</option>
                                ))}
                            </select>
                            <label className="flex flex-col">
                                <input placeholder="Your name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="rounded-lg border border-slate-200 px-3 py-2" />
                                {errors.name && <span className="text-xs text-red-600">{errors.name}</span>}
                            </label>
                            <label className="flex flex-col">
                                <input placeholder="Email" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className="rounded-lg border border-slate-200 px-3 py-2" />
                                {errors.email && <span className="text-xs text-red-600">{errors.email}</span>}
                            </label>
                            <button type="submit" className="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white">
                                Book {slot.local_time}
                            </button>
                        </form>
                    )}

                    {booking && (
                        <button type="button" onClick={cancel} className="self-start rounded-lg border border-red-300 px-4 py-2 text-red-700">
                            Cancel this lesson
                        </button>
                    )}
                </section>
            )}

            {message && (
                <p role="status" className={message.kind === 'ok' ? 'text-emerald-700' : 'text-red-700'}>
                    {message.text}
                </p>
            )}
        </main>
    );
}

createRoot(document.getElementById('app')).render(
    <StrictMode>
        <App />
    </StrictMode>,
);

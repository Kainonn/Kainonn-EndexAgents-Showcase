import { router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { WorkspaceCard } from '@/types/workspace';

type ManagementResult =
    | 'contacted_waiting'
    | 'no_answer'
    | 'interested'
    | 'not_interested'
    | 'closed';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    card: WorkspaceCard | null;
    onSuccess?: (message: string) => void;
    onError?: (message: string) => void;
};

const resultOptions: Array<{ value: ManagementResult; label: string }> = [
    { value: 'contacted_waiting', label: 'Contactado, esperando respuesta' },
    { value: 'no_answer', label: 'No respondió' },
    { value: 'interested', label: 'Interesado' },
    { value: 'not_interested', label: 'No interesado / perdido' },
    { value: 'closed', label: 'Cerrado' },
];

export function LeadManageModal({ open, onOpenChange, card, onSuccess, onError }: Props) {
    const [result, setResult] = useState<ManagementResult>('contacted_waiting');
    const [note, setNote] = useState('');
    const [followUpAt, setFollowUpAt] = useState('');
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open || card === null) return;

        setResult('contacted_waiting');
        setNote('');

        const defaultDate = new Date();
        defaultDate.setDate(defaultDate.getDate() + 1);
        defaultDate.setHours(18, 0, 0, 0);
        setFollowUpAt(formatDateTimeLocal(defaultDate));
    }, [open, card]);

    const previewStatus = useMemo(() => {
        switch (result) {
            case 'contacted_waiting':
            case 'no_answer':
                return 'waiting_response';
            case 'interested':
                return 'interested';
            case 'not_interested':
                return 'not_interested';
            case 'closed':
                return 'closed';
            default:
                return card?.commercial_status ?? 'in_contact';
        }
    }, [card?.commercial_status, result]);

    const previewActionType = useMemo(() => {
        if (result === 'no_answer') return 'call_started';
        if (result === 'closed' || result === 'not_interested') return 'status_updated';

        if (card?.recommended_channel === 'whatsapp') return 'whatsapp_opened';
        if (card?.recommended_channel === 'phone') return 'call_started';
        if (card?.recommended_channel === 'email') return 'email_prepared';

        return 'contact_initiated';
    }, [card?.recommended_channel, result]);

    const canSubmit = card !== null && !saving;

    const getCsrfToken = (): string => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        return token ?? '';
    };

    const postJson = async (url: string, method: 'POST' | 'PATCH', payload: Record<string, unknown>) => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            throw new Error('request_failed');
        }
    };

    const handleSubmit = async () => {
        if (!canSubmit || card === null) return;

        try {
            setSaving(true);

            await postJson(`/endex/workspace/leads/${card.id}/action`, 'POST', {
                action_type: previewActionType,
                channel: card.recommended_channel,
                notes: note.trim() !== '' ? note.trim() : undefined,
            });

            await postJson(`/endex/workspace/leads/${card.id}/status`, 'PATCH', {
                commercial_status: previewStatus,
                reason: note.trim() !== '' ? note.trim() : undefined,
            });

            if (followUpAt !== '') {
                await postJson(`/endex/workspace/leads/${card.id}/follow-up`, 'POST', {
                    next_follow_up_at: toServerDateTime(followUpAt),
                    notes: note.trim() !== '' ? note.trim() : undefined,
                });
            }

            router.get(window.location.pathname + window.location.search, {}, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });

            onOpenChange(false);
            onSuccess?.('Gestión registrada correctamente.');
        } catch {
            onError?.('No se pudo registrar la gestión. Revisa los datos e intenta de nuevo.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange} modal={false}>
            <DialogContent
                className="sm:max-w-lg"
                hideOverlay
                showCloseButton={false}
                onEscapeKeyDown={(event) => event.preventDefault()}
                onPointerDownOutside={(event) => event.preventDefault()}
                onInteractOutside={(event) => event.preventDefault()}
            >
                <DialogHeader>
                    <DialogTitle>Registrar gestión</DialogTitle>
                    <DialogDescription>
                        {card?.company_name ?? 'Lead'} - Resultado del contacto y siguiente paso operativo.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-3">
                    <div className="grid gap-1.5">
                        <Label htmlFor="result" className="text-xs">Resultado</Label>
                        <Select value={result} onValueChange={(value) => setResult(value as ManagementResult)}>
                            <SelectTrigger id="result" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {resultOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="follow-up-date" className="text-xs">Proximo seguimiento (fecha y hora)</Label>
                        <input
                            id="follow-up-date"
                            type="datetime-local"
                            value={followUpAt}
                            onChange={(e) => setFollowUpAt(e.target.value)}
                            min={formatDateTimeLocal(new Date())}
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus:border-ring focus:outline-none focus:ring-[3px] focus:ring-ring/50"
                        />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="note" className="text-xs">Nota de gestión (opcional)</Label>
                        <textarea
                            id="note"
                            value={note}
                            onChange={(e) => setNote(e.target.value)}
                            rows={4}
                            maxLength={2000}
                            placeholder="Ej. Contacté al gerente, pidió propuesta para el lunes"
                            className="w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus:border-ring focus:outline-none focus:ring-[3px] focus:ring-ring/50"
                        />
                    </div>

                    <div className="rounded-md border border-dashed bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
                        Se registrara una accion comercial, se actualizara el estado a
                        <span className="font-semibold text-foreground"> {previewStatus}</span>
                        {followUpAt !== '' ? ' y se programara seguimiento.' : '.'}
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>
                        Cancelar
                    </Button>
                    <Button onClick={handleSubmit} disabled={!canSubmit}>
                        {saving ? 'Guardando...' : 'Completar gestion'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function formatDateTimeLocal(date: Date): string {
    const tzOffset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() - tzOffset).toISOString().slice(0, 16);
}

function toServerDateTime(value: string): string {
    if (!value) return value;

    return value.replace('T', ' ') + ':00';
}

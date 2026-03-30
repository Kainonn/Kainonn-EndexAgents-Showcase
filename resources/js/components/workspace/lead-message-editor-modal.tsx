import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    leadId: number;
    companyName: string;
    initialSubject: string;
    initialBody: string;
    messageSource?: 'human_edited' | 'ai_generated' | 'fallback';
    messageUpdatedAt?: string | null;
};

export function LeadMessageEditorModal({
    open,
    onOpenChange,
    leadId,
    companyName,
    initialSubject,
    initialBody,
    messageSource,
    messageUpdatedAt,
}: Props) {
    const [subject, setSubject] = useState(initialSubject);
    const [body, setBody] = useState(initialBody);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (open) {
            setSubject(initialSubject ?? '');
            setBody(initialBody ?? '');
        }
    }, [open, initialSubject, initialBody, leadId]);

    const handleSave = () => {
        if (!body.trim() || saving) return;

        setSaving(true);
        router.patch(
            `/endex/workspace/leads/${leadId}/message`,
            { subject, body },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    setSaving(false);
                    onOpenChange(false);
                },
                onError: () => setSaving(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Editar mensaje</DialogTitle>
                    <DialogDescription>
                        {companyName} &mdash; Ajusta el texto efectivo antes de contactar.
                    </DialogDescription>
                </DialogHeader>

                <div className="rounded-md border border-dashed bg-muted/30 px-3 py-2 text-xs text-muted-foreground">
                    <p>
                        Fuente actual:{' '}
                        <span className="font-medium text-foreground">
                            {messageSource === 'human_edited'
                                ? 'Editado por humano'
                                : messageSource === 'ai_generated'
                                    ? 'Generado por IA'
                                    : 'Fallback'}
                        </span>
                    </p>
                    <p>
                        Actualizado:{' '}
                        <span className="font-medium text-foreground">
                            {messageUpdatedAt
                                ? new Date(messageUpdatedAt).toLocaleString('es-MX')
                                : 'Sin fecha (fallback)'}
                        </span>
                    </p>
                </div>

                <div className="grid gap-3">
                    <div className="grid gap-1.5">
                        <Label htmlFor="msg-subject" className="text-xs">
                            Asunto
                        </Label>
                        <Input
                            id="msg-subject"
                            value={subject}
                            onChange={(e) => setSubject(e.target.value)}
                            placeholder="Asunto del mensaje"
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="msg-body" className="text-xs">
                            Mensaje
                        </Label>
                        <textarea
                            id="msg-body"
                            value={body}
                            onChange={(e) => setBody(e.target.value)}
                            className="min-h-[40vh] w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus:border-ring focus:outline-none focus:ring-[3px] focus:ring-ring/50"
                            placeholder="Escribe o edita el mensaje..."
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>
                        Cancelar
                    </Button>
                    <Button onClick={handleSave} disabled={!body.trim() || saving}>
                        {saving ? 'Guardando...' : 'Guardar mensaje'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

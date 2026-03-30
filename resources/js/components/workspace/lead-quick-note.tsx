import { router } from '@inertiajs/react';
import { MessageSquare, Save } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';

type Props = {
    leadId: number;
    currentNote: string | null;
};

export function LeadQuickNote({ leadId, currentNote }: Props) {
    const [open, setOpen] = useState(false);
    const [note, setNote] = useState(currentNote ?? '');
    const [saving, setSaving] = useState(false);
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    const isDirty = note !== (currentNote ?? '');

    const handleSave = () => {
        if (!isDirty || saving) return;

        setSaving(true);
        router.patch(
            `/endex/workspace/leads/${leadId}/note`,
            { commercial_notes: note },
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    setSaving(false);
                    setOpen(false);
                },
            },
        );
    };

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => {
                    setOpen(true);
                    requestAnimationFrame(() => textareaRef.current?.focus());
                }}
                className="group flex items-center gap-1 text-[11px] text-muted-foreground hover:text-foreground transition-colors"
            >
                <MessageSquare className="size-3" />
                {currentNote ? (
                    <span className="max-w-[180px] truncate">{currentNote}</span>
                ) : (
                    <span className="italic">Agregar nota</span>
                )}
            </button>
        );
    }

    return (
        <div className="space-y-1.5">
            <textarea
                ref={textareaRef}
                value={note}
                onChange={(e) => setNote(e.target.value)}
                placeholder="Nota comercial rápida..."
                className="w-full resize-none rounded-md border border-input bg-transparent px-2 py-1.5 text-xs shadow-xs focus:border-ring focus:outline-none focus:ring-1 focus:ring-ring/50"
                rows={2}
                maxLength={5000}
                autoFocus
            />
            <div className="flex items-center gap-1.5">
                <Button
                    size="sm"
                    variant="default"
                    className="h-6 px-2 text-[11px]"
                    onClick={handleSave}
                    disabled={!isDirty || saving}
                >
                    <Save className="size-3" />
                    {saving ? 'Guardando...' : 'Guardar'}
                </Button>
                <Button
                    size="sm"
                    variant="ghost"
                    className="h-6 px-2 text-[11px]"
                    onClick={() => {
                        setNote(currentNote ?? '');
                        setOpen(false);
                    }}
                >
                    Cerrar
                </Button>
            </div>
        </div>
    );
}

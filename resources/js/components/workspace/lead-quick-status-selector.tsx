import { router } from '@inertiajs/react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { StatusOptions } from '@/types/workspace';

const CRITICAL_STATUSES = ['closed', 'not_interested'];

type Props = {
    leadId: number;
    currentStatus: string;
    statusOptions: StatusOptions;
};

export function LeadQuickStatusSelector({ leadId, currentStatus, statusOptions }: Props) {
    const [saving, setSaving] = useState(false);
    const [pendingStatus, setPendingStatus] = useState<string | null>(null);

    const commitChange = (newStatus: string) => {
        setSaving(true);
        router.patch(
            `/endex/workspace/leads/${leadId}/status`,
            { commercial_status: newStatus },
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setSaving(false),
            },
        );
    };

    const handleChange = (newStatus: string) => {
        if (newStatus === currentStatus || saving) return;

        if (CRITICAL_STATUSES.includes(newStatus)) {
            setPendingStatus(newStatus);
            return;
        }

        commitChange(newStatus);
    };

    const confirmCritical = () => {
        if (pendingStatus) {
            commitChange(pendingStatus);
            setPendingStatus(null);
        }
    };

    const cancelCritical = () => {
        setPendingStatus(null);
    };

    const pendingLabel = pendingStatus ? statusOptions[pendingStatus]?.label ?? pendingStatus : '';

    return (
        <>
            <Select value={currentStatus} onValueChange={handleChange} disabled={saving}>
                <SelectTrigger size="sm" className="h-7 w-full text-[11px]">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {Object.entries(statusOptions).map(([value, opt]) => (
                        <SelectItem key={value} value={value} className="text-xs">
                            {opt.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <AlertDialog open={pendingStatus !== null} onOpenChange={(open) => { if (!open) cancelCritical(); }}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Cambiar a "{pendingLabel}"?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción marca el lead como {pendingStatus === 'closed' ? 'cerrado' : 'perdido'}.
                            {pendingStatus === 'closed' ? ' No se podrán hacer más cambios de estado.' : ' Podrás revertirlo después.'}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={confirmCritical}>Confirmar</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}

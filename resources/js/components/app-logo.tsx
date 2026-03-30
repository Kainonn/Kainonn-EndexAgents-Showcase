export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center rounded-lg border border-[#ffffff24] bg-white/95 p-1 shadow-sm">
                <img src="/logo/endex-logo.png" alt="EndexAgents" className="size-full object-contain" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-semibold text-sidebar-foreground">
                    EndexAgents
                </span>
                <span className="truncate text-[11px] uppercase tracking-[0.16em] text-sidebar-foreground/70">B2B Pipeline</span>
            </div>
        </>
    );
}

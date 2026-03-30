import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col overflow-hidden bg-[#060708] p-6 md:p-10">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_14%,rgba(255,68,51,0.18),transparent_42%),radial-gradient(circle_at_92%_16%,rgba(255,117,15,0.12),transparent_38%),linear-gradient(180deg,#060708_0%,#0b0f12_100%)]" />
            <div className="pointer-events-none absolute top-14 -left-32 h-56 w-56 rounded-full border border-[#ff750f33]" />
            <div className="pointer-events-none absolute -right-24 bottom-12 h-64 w-64 rounded-full border border-[#ff443344]" />

            <div className="relative mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center">
                <div className="mb-8 flex items-center justify-between">
                    <Link href={home()} className="inline-flex items-center gap-3">
                        <img
                            src="/logo/endex-logo.png"
                            alt="Endex"
                            className="h-11 w-11 rounded-lg bg-white/95 p-1"
                        />
                        <div>
                            <p className="text-lg font-semibold tracking-tight text-white">
                                Endex<span className="text-[#FF4433]">Agents</span>
                            </p>
                            <p className="text-[11px] uppercase tracking-[0.22em] text-[#b3b2ad]">
                                Acceso seguro
                            </p>
                        </div>
                    </Link>

                    <p className="hidden rounded-full border border-[#ffffff26] bg-[#ffffff0d] px-3 py-1 text-[11px] uppercase tracking-[0.16em] text-[#d8d6d1] sm:block">
                        Endex Identity
                    </p>
                </div>

                <div className="mx-auto w-full max-w-md rounded-3xl border border-[#ffffff24] bg-[#0f1418cc] p-6 shadow-2xl shadow-black/30 backdrop-blur-sm sm:p-8">
                    <div className="mb-6 space-y-2 text-center">
                        <h1 className="text-2xl font-semibold tracking-tight text-white">
                            {title}
                        </h1>
                        <p className="text-sm leading-relaxed text-[#bdbab4]">
                            {description}
                        </p>
                    </div>

                    <div className="[&_a]:text-[#ff8a7f] [&_a]:hover:text-[#ffb2aa] [&_button]:transition-colors [&_button[data-slot='button']]:border-[#ff4433] [&_button[data-slot='button']]:bg-[#ff4433] [&_button[data-slot='button']]:text-white [&_button[data-slot='button']]:hover:bg-[#ff5a47] [&_input]:border-[#ffffff2b] [&_input]:bg-[#0a0f14] [&_input]:text-white [&_input]:placeholder:text-[#8f8b84] [&_label]:text-[#e0ddd8]">
                        {children}
                    </div>
                </div>

                <p className="mt-6 text-center text-xs text-[#8f8b84]">
                    Uso interno de EndexAgents. Single tenant en evolucion hacia SaaS.
                </p>
            </div>
        </div>
    );
}

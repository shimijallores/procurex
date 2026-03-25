<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Procurex Templates</title>
    <link rel="icon" href="{{ asset('images/batangas-seal.png') }}" type="image/png">
    @vite(['resources/css/app.css'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap');

        :root {
            --bg-a: #f8fafc;
            --bg-b: #e2e8f0;
            --card: #ffffff;
            --line: #dbe4ef;
            --ink: #0f172a;
            --muted: #475569;
            --brand: #1d4ed8;
            --brand-soft: #dbeafe;
            --accent: #0f766e;
        }

        body {
            font-family: 'Manrope', sans-serif;
            color: var(--ink);
            min-height: 100vh;
        }

        .hero-title {
            font-family: 'Sora', sans-serif;
            letter-spacing: -0.03em;
        }

        .mesh-card {
            background:
                linear-gradient(120deg, rgba(255, 255, 255, 0.85), rgba(255, 255, 255, 0.96)),
                radial-gradient(circle at 0% 0%, rgba(59, 130, 246, 0.15), transparent 45%),
                radial-gradient(circle at 100% 100%, rgba(15, 118, 110, 0.14), transparent 40%);
            border: 1px solid var(--line);
            backdrop-filter: blur(4px);
        }

        .template-card {
            border: 1px solid var(--line);
            background: var(--card);
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }

        .template-card:hover {
            transform: translateY(-3px);
            border-color: #93c5fd;
            box-shadow: 0 16px 28px rgba(15, 23, 42, 0.1);
        }

        .badge {
            background: var(--brand-soft);
            color: var(--brand);
        }

        .download-btn {
            background: linear-gradient(90deg, var(--brand), #2563eb);
        }

        .download-btn:hover {
            background: linear-gradient(90deg, #1e40af, #1d4ed8);
        }
    </style>
</head>

<body>
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <section class="mesh-card overflow-hidden rounded-3xl p-6 sm:p-8 lg:p-10">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-3xl space-y-5">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/batangas-seal.png') }}" alt="Batangas Seal" class="h-14 w-14 rounded-full border border-slate-200 bg-white p-1" />
                        <img src="{{ asset('images/bagong-pilipinas.png') }}" alt="Bagong Pilipinas" class="h-14 w-auto rounded-md border border-slate-200 bg-white px-2 py-1" />
                    </div>

                    <div class="space-y-3">
                        <p class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold tracking-wide text-sky-700">PROCUREX TEMPLATES</p>
                        <h1 class="hero-title text-3xl font-extrabold leading-tight sm:text-4xl lg:text-5xl">
                            Standard Templates for Upload and Submission
                        </h1>
                        <p class="max-w-2xl text-sm text-slate-600 sm:text-base">
                            Download the official numbered templates below before creating your procurement submissions.
                            Each file is the latest standard format used by this system.
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-sm sm:p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Quick Access</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ count($templates) }} Templates</p>
                    <p class="mt-1 text-sm text-slate-600">Click any card to start downloading.</p>
                    <a href="{{ route('login') }}" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
                        Go to Login
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <section class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:mt-10 lg:grid-cols-3 lg:gap-5">
            @foreach ($templates as $template)
            <a href="{{ route('templates.download', $template['id']) }}" class="template-card group rounded-2xl p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Template {{ $template['id'] }}
                        </p>
                        <h2 class="mt-2 text-lg font-extrabold leading-tight text-slate-900">
                            {{ $template['title'] }}
                        </h2>
                    </div>
                    <span class="badge rounded-full px-3 py-1 text-xs font-bold">
                        {{ $template['type'] }}
                    </span>
                </div>

                <p class="mt-4 text-sm text-slate-600">
                    {{ $template['file'] }}
                </p>

                <div class="mt-5 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white download-btn">
                    Download Template
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v11m0 0l4-4m-4 4l-4-4m-5 8h18" />
                    </svg>
                </div>
            </a>
            @endforeach
        </section>
    </main>
</body>

</html>
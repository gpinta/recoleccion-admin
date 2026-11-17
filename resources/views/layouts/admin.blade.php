<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel Admin')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        /* Paleta de temas de Recolección UY. */
        :root {
            --tema-principal: #0891B2;
            --tema-hover: #0E7490;
            --tema-hover-fuerte: #155E75;
            --tema-fondo: #ECFEFF;
            --tema-titulo: #155E75;
            --tema-texto-nav: #FFFFFF;
            --tema-texto-boton: #FFFFFF;
            --tema-focus: #67E8F9;
            --tema-suave: #CFFAFE;
        }
        html.tema-verde {
            --tema-principal: #15803D;
            --tema-hover: #166534;
            --tema-hover-fuerte: #14532D;
            --tema-fondo: #F0FDF4;
            --tema-titulo: #14532D;
            --tema-texto-nav: #FFFFFF;
            --tema-texto-boton: #FFFFFF;
            --tema-focus: #86EFAC;
            --tema-suave: #DCFCE7;
        }
        html.tema-coral {
            --tema-principal: #E76F61;
            --tema-hover: #CF5B4E;
            --tema-hover-fuerte: #9D3830;
            --tema-fondo: #FFF1EE;
            --tema-titulo: #9D3830;
            --tema-texto-nav: #FFFFFF;
            --tema-texto-boton: #FFFFFF;
            --tema-focus: #F4A99F;
            --tema-suave: #FFE0D9;
        }
        html.tema-amarillo {
            --tema-principal: #F4D35E;
            --tema-hover: #E9BA39;
            --tema-hover-fuerte: #C79A27;
            --tema-fondo: #FFF9E6;
            --tema-titulo: #85621C;
            --tema-texto-nav: #554115;
            --tema-texto-boton: #44340F;
            --tema-focus: #E9D48B;
            --tema-suave: #FEF3C7;
        }
        html.tema-gris {
            --tema-principal: #374151;
            --tema-hover: #1F2937;
            --tema-hover-fuerte: #111827;
            --tema-fondo: #F3F4F6;
            --tema-titulo: #111827;
            --tema-texto-nav: #FFFFFF;
            --tema-texto-boton: #FFFFFF;
            --tema-focus: #9CA3AF;
            --tema-suave: #E5E7EB;
        }
        .tema-fondo { background-color: var(--tema-fondo); }
        .tema-nav { background-color: var(--tema-principal); color: var(--tema-texto-nav); }
        .tema-nav-activo { background-color: var(--tema-hover); }
        .tema-rol { background-color: var(--tema-hover-fuerte); color: var(--tema-texto-nav); }
        .tema-titulo { color: var(--tema-titulo); }
        .tema-enlace { color: var(--tema-principal); }
        .tema-enlace:hover { color: var(--tema-hover-fuerte); }
        .tema-boton { background-color: var(--tema-principal); color: var(--tema-texto-boton); }
        .tema-boton:hover { background-color: var(--tema-hover); }
        .tema-boton-nav { background-color: var(--tema-hover); color: var(--tema-texto-nav); }
        .tema-boton-nav:hover { background-color: var(--tema-hover-fuerte); }
        .tema-suave { background-color: var(--tema-suave); color: var(--tema-titulo); }
        .tema-input:focus {
            outline: none;
            border-color: var(--tema-principal);
            box-shadow: 0 0 0 2px var(--tema-focus);
        }
        .tema-selector {
            color: var(--tema-texto-nav);
            background-color: var(--tema-principal);
            border: 1px solid currentColor;
            border-radius: .25rem;
            padding: .25rem;
            font-size: .75rem;
            cursor: pointer;
        }
        .tema-selector option { color: #111827; background-color: #FFFFFF; }
        .tema-selector:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }
    </style>
    <script>
        // Recuperar el tema antes de renderizar la interfaz.
        (function () {
            try {
                const tema = localStorage.getItem('recoleccion-admin-tema');
                if (['verde', 'coral', 'amarillo', 'gris'].includes(tema)) {
                    document.documentElement.classList.add('tema-' + tema);
                }
            } catch (error) {
                // El navegador mantiene el tema predeterminado.
            }
        })();
    </script>
</head>
<body class="tema-fondo min-h-screen flex flex-col">
    <nav class="tema-nav shadow">
        <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-4">
            {{-- Logo / Marca (no es enlace) --}}
            <div class="font-bold text-lg flex items-center gap-2">
                <span>🗑️</span>
                <span>Recolección UY!</span>
                <img src="{{ asset('images/bandera-uy.png') }}" alt="Bandera de Uruguay" class="h-5 w-auto">
            </div>

            {{-- Menú solo si está autenticado --}}
            @auth
                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('admin.dashboard') }}" class="text-sm hover:underline {{ request()->routeIs('admin.dashboard') ? 'font-semibold tema-nav-activo px-3 py-1 rounded' : '' }}">Inicio</a>
                    <a href="{{ route('admin.denuncias.index') }}" class="text-sm hover:underline {{ request()->routeIs('admin.denuncias.*') ? 'font-semibold tema-nav-activo px-3 py-1 rounded' : '' }}">Denuncias</a>
                    <a href="{{ route('admin.barrios.index') }}" class="text-sm hover:underline {{ request()->routeIs('admin.barrios.*') ? 'font-semibold tema-nav-activo px-3 py-1 rounded' : '' }}">Barrios</a>
                    <a href="{{ route('admin.municipios.index') }}" class="text-sm hover:underline {{ request()->routeIs('admin.municipios.*') ? 'font-semibold tema-nav-activo px-3 py-1 rounded' : '' }}">Municipios</a>
                    <a href="{{ route('admin.camiones.index') }}" class="text-sm hover:underline {{ request()->routeIs('admin.camiones.*') ? 'font-semibold tema-nav-activo px-3 py-1 rounded' : '' }}">Camiones</a>
                    
                    <div class="flex items-center gap-3 ml-4">
                        <span class="text-sm opacity-90">
                            {{ auth()->user()->getFirstAttribute('displayname') ?: auth()->user()->getFirstAttribute('cn') ?: auth()->user()->getUserPrincipalName() }}
                        </span>

                        <span class="text-xs tema-rol px-2 py-1 rounded">
                            {{ auth()->user()->adminRole()?->label() ?? 'Sin rol' }}
                        </span>

                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="text-sm tema-boton-nav px-3 py-1 rounded transition">Salir</button>
                        </form>
                    </div>
                </div>
            @else
                {{-- Texto opcional en pantalla de login --}}
                <span class="text-sm opacity-90">Panel Administrativo</span>
            @endauth
                <select id="selector-tema" class="tema-selector" aria-label="Seleccionar colores">
                    <option value="celeste">Azul turquesa</option>
                    <option value="verde">Verde esmeralda</option>
                    <option value="coral">Rojo coral</option>
                    <option value="amarillo">Amarillo dorado</option>
                    <option value="gris">Gris grafito</option>
                </select>
        </div>
    </nav>

    <main class="flex-grow @if(!auth()->check()) flex items-center justify-center @endif max-w-7xl mx-auto w-full px-4 py-6">
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        @yield('content')
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selector = document.getElementById('selector-tema');
            if (!selector) return;

            const temas = ['celeste', 'verde', 'coral', 'amarillo', 'gris'];
            const clases = temas.filter(tema => tema !== 'celeste')
                .map(tema => 'tema-' + tema);

            function aplicarTema(tema) {
                if (!temas.includes(tema)) tema = 'celeste';

                document.documentElement.classList.remove(...clases);
                if (tema !== 'celeste') {
                    document.documentElement.classList.add('tema-' + tema);
                }
                selector.value = tema;

                try {
                    localStorage.setItem('recoleccion-admin-tema', tema);
                } catch (error) {
                    // El tema sigue funcionando durante la visita.
                }
            }

            selector.addEventListener('change', () => aplicarTema(selector.value));
            const actual = temas.find(tema =>
                document.documentElement.classList.contains('tema-' + tema)
            ) || 'celeste';
            aplicarTema(actual);
        });
    </script>
</body>
</html>
@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/jquery-ui.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/hope-ui.min.css') }}" />
@endsection
@section('nav')
    @if (auth()->user()->hasRole('admin'))
        <ul class="nav justify-content-center mb-3">
            <li class="nav-item" style="margin: 0 10px 5px 10px;">
                <a class="nav-link btn btn-secondary" href="{{ route('cashClose.create') }}">Registro</a>
            </li>
            <li class="nav-item" style="margin: 0 10px 5px 10px;">
                <a class="nav-link btn btn-primary active" href="{{ route('cashClose.index') }}">Histórico</a>
            </li>
        </ul>
    @endif
@endsection
@section('header')
    <h2>Histórico de Cierre de Caja</h2>
    <p>Lista de Cierre de Caja</p>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-body">

                    {{-- Filtros --}}
                    <form class="mb-4" id="filter-form" method="GET" action="{{ route('cashClose.index') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Fecha</label>
                                <input type="date" class="form-control" name="date" id="date"
                                    value="{{ $date ?? now()->format('Y-m-d') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Turno</label>
                                <select class="form-select" name="shift" id="shift">
                                    <option value="">Todos</option>
                                    <option value="0" {{ $shift === '0' ? 'selected' : '' }}>Mañana</option>
                                    <option value="1" {{ $shift === '1' ? 'selected' : '' }}>Tarde</option>
                                </select>
                            </div>

                            @if ($isAdmin)
                                <div class="col-md-3">
                                    <label class="form-label">Sede</label>
                                    <select class="form-select" name="location_id" id="location_id">
                                        <option value="">Todas las sedes</option>
                                        @foreach ($locations as $location)
                                            <option value="{{ $location->id }}"
                                                {{ (string) $location_id === (string) $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="col-md-3">
                                <label class="form-label">Usuario</label>
                                <select class="form-select" name="user_id" id="user_id">
                                    <option value="">Todos</option>
                                    @foreach ($users as $u)
                                        <option value="{{ $u->id }}"
                                            {{ (string) $user_id === (string) $u->id ? 'selected' : '' }}>
                                            {{ $u->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <a href="{{ route('cashClose.index', ['date' => now()->format('Y-m-d')]) }}"
                                    class="btn btn-warning ms-2">Hoy</a>
                            </div>
                        </div>
                    </form>

                    <label class="d-block my-4">
                        Turno: {{ $shift === '0' ? 'Mañana' : ($shift === '1' ? 'Tarde' : 'Todos') }}
                    </label>

                    {{-- Resumen de ventas --}}
                    <div class="table-responsive mb-4">
                        <h5>Resumen del período seleccionado</h5>
                        <table class="table table-bordered table-sm w-auto"  id="cash-table">
                            <thead>
                                <tr>
                                    <th colspan="2" class="text-center">Ventas</th>
                                </tr>
                                <tr>
                                    <th>Método de pago</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ventas_payment_methods as $payment_method)
                                    <tr>
                                        <td>{{ ucfirst(strtolower($payment_method->name)) }}</td>
                                        <td align="right">S/ {{ number_format($payment_method->total, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="fw-bold">
                                    <td>TOTAL VENTAS</td>
                                    <td align="right">S/ {{ number_format($total_ventas, 2) }}</td>
                                </tr>
                                <tr class="fw-bold">
                                    <td colspan="2">Efectivo = S/ {{ number_format($efectivo, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <button class="btn me-2 mt-3 btn-success" id="btnGuardar">Imprimir</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('assets/js/hope-ui.min.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit cuando cambian los filtros
            const form = document.getElementById('filter-form');
            const dateInput = document.getElementById('date');
            const shiftSelect = document.getElementById('shift');
            const locationSelect = document.getElementById('location_id');
            const userSelect = document.getElementById('user_id');

            // Auto submit en cambios
            [dateInput, shiftSelect, userSelect].forEach(element => {
                if (element) {
                    element.addEventListener('change', function() {
                        form.submit();
                    });
                }
            });

            if (locationSelect) {
                locationSelect.addEventListener('change', function() {
                    form.submit();
                });
            }

            // Asegurar fecha válida al enviar
            form.addEventListener('submit', function(e) {
                if (!dateInput.value) {
                    dateInput.value = new Date().toISOString().split('T')[0];
                }
            });
        });
    </script>

    <script>
        const ConectorPluginV3 = (() => {

            /**
             * Una clase para interactuar con el plugin v3
             *
             * @date 2022-09-28
             * @author parzibyte
             * @see https://parzibyte.me/blog
             */

            class Operacion {
                constructor(nombre, argumentos) {
                    this.nombre = nombre;
                    this.argumentos = argumentos;
                }
            }

            class ConectorPlugin {

                static URL_PLUGIN_POR_DEFECTO = "http://localhost:8000";
                static Operacion = Operacion;
                static TAMAÑO_IMAGEN_NORMAL = 0;
                static TAMAÑO_IMAGEN_DOBLE_ANCHO = 1;
                static TAMAÑO_IMAGEN_DOBLE_ALTO = 2;
                static TAMAÑO_IMAGEN_DOBLE_ANCHO_Y_ALTO = 3;
                static TAMAÑO_IMAGEN_DOBLE_ANCHO_Y_ALTO = 3;
                static ALINEACION_IZQUIERDA = 0;
                static ALINEACION_CENTRO = 1;
                static ALINEACION_DERECHA = 2;
                static RECUPERACION_QR_BAJA = 0;
                static RECUPERACION_QR_MEDIA = 1;
                static RECUPERACION_QR_ALTA = 2;
                static RECUPERACION_QR_MEJOR = 3;


                constructor(ruta, serial) {
                    if (!ruta) ruta = ConectorPlugin.URL_PLUGIN_POR_DEFECTO;
                    if (!serial) serial = "";
                    this.ruta = ruta;
                    this.serial = serial;
                    this.operaciones = [];
                    return this;
                }

                CargarImagenLocalEImprimir(ruta, tamaño, maximoAncho) {
                    this.operaciones.push(new ConectorPlugin.Operacion("CargarImagenLocalEImprimir", Array.from(
                        arguments)));
                    return this;
                }
                Corte(lineas) {
                    this.operaciones.push(new ConectorPlugin.Operacion("Corte", Array.from(arguments)));
                    return this;
                }
                CorteParcial() {
                    this.operaciones.push(new ConectorPlugin.Operacion("CorteParcial", Array.from(arguments)));
                    return this;
                }
                DefinirCaracterPersonalizado(caracterRemplazo, matriz) {
                    this.operaciones.push(new ConectorPlugin.Operacion("DefinirCaracterPersonalizado", Array
                        .from(arguments)));
                    return this;
                }
                DescargarImagenDeInternetEImprimir(urlImagen, tamaño, maximoAncho) {
                    this.operaciones.push(new ConectorPlugin.Operacion("DescargarImagenDeInternetEImprimir",
                        Array.from(arguments)));
                    return this;
                }
                DeshabilitarCaracteresPersonalizados() {
                    this.operaciones.push(new ConectorPlugin.Operacion("DeshabilitarCaracteresPersonalizados",
                        Array.from(arguments)));
                    return this;
                }
                DeshabilitarElModoDeCaracteresChinos() {

                    this.operaciones.push(new ConectorPlugin.Operacion("DeshabilitarElModoDeCaracteresChinos",
                        Array.from(arguments)));
                    return this;
                }
                EscribirTexto(texto) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EscribirTexto", Array.from(arguments)));
                    return this;
                }
                EstablecerAlineacion(alineacion) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerAlineacion", Array.from(
                        arguments)));
                    return this;
                }
                EstablecerEnfatizado(enfatizado) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerEnfatizado", Array.from(
                        arguments)));
                    return this;
                }
                EstablecerFuente(fuente) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerFuente", Array.from(
                        arguments)));
                    return this;
                }
                EstablecerImpresionAlReves(alReves) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerImpresionAlReves", Array.from(
                        arguments)));
                    return this;
                }
                EstablecerImpresionBlancoYNegroInversa(invertir) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerImpresionBlancoYNegroInversa",
                        Array.from(arguments)));
                    return this;
                }
                EstablecerRotacionDe90Grados(rotar) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerRotacionDe90Grados", Array
                        .from(arguments)));
                    return this;
                }
                EstablecerSubrayado(subrayado) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerSubrayado", Array.from(
                        arguments)));
                    return this;
                }
                EstablecerTamañoFuente(multiplicadorAncho, multiplicadorAlto) {
                    this.operaciones.push(new ConectorPlugin.Operacion("EstablecerTamañoFuente", Array.from(
                        arguments)));
                    return this;
                }
                Feed(lineas) {
                    this.operaciones.push(new ConectorPlugin.Operacion("Feed", Array.from(arguments)));
                    return this;
                }
                HabilitarCaracteresPersonalizados() {
                    this.operaciones.push(new ConectorPlugin.Operacion("HabilitarCaracteresPersonalizados",
                        Array.from(arguments)));
                    return this;
                }
                HabilitarElModoDeCaracteresChinos() {
                    this.operaciones.push(new ConectorPlugin.Operacion("HabilitarElModoDeCaracteresChinos",
                        Array.from(arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasCodabar(contenido, alto, ancho, tamañoImagen) {

                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasCodabar", Array
                        .from(arguments)));
                    return this;
                }

                ImprimirCodigoDeBarrasCode128(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasCode128", Array
                        .from(arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasCode39(contenido, incluirSumaDeVerificacion, modoAsciiCompleto, alto, ancho,
                    tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasCode39", Array
                        .from(arguments)));
                    return this;
                }

                ImprimirCodigoDeBarrasCode93(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasCode93", Array
                        .from(arguments)));
                    return this;
                }

                ImprimirCodigoDeBarrasEan(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasEan", Array.from(
                        arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasEan8(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasEan8", Array.from(
                        arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasPdf417(contenido, nivelSeguridad, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasPdf417", Array
                        .from(arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasTwoOfFiveITF(contenido, intercalado, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasTwoOfFiveITF",
                        Array.from(arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasUpcA(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasUpcA", Array.from(
                        arguments)));
                    return this;
                }
                ImprimirCodigoDeBarrasUpcE(contenido, alto, ancho, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoDeBarrasUpcE", Array.from(
                        arguments)));
                    return this;
                }
                ImprimirCodigoQr(contenido, anchoMaximo, nivelRecuperacion, tamañoImagen) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirCodigoQr", Array.from(
                        arguments)));
                    return this;
                }
                ImprimirImagenEnBase64(imagenCodificadaEnBase64, tamaño, maximoAncho) {
                    this.operaciones.push(new ConectorPlugin.Operacion("ImprimirImagenEnBase64", Array.from(
                        arguments)));
                    return this;
                }

                Iniciar() {
                    this.operaciones.push(new ConectorPlugin.Operacion("Iniciar", Array.from(arguments)));
                    return this;
                }

                Pulso(pin, tiempoEncendido, tiempoApagado) {
                    this.operaciones.push(new ConectorPlugin.Operacion("Pulso", Array.from(arguments)));
                    return this;
                }

                TextoSegunPaginaDeCodigos(numeroPagina, pagina, texto) {
                    this.operaciones.push(new ConectorPlugin.Operacion("TextoSegunPaginaDeCodigos", Array.from(
                        arguments)));
                    return this;
                }


                static async obtenerImpresoras(ruta) {
                    if (ruta) ConectorPlugin.URL_PLUGIN_POR_DEFECTO = ruta;
                    const response = await fetch(ConectorPlugin.URL_PLUGIN_POR_DEFECTO + "/impresoras");
                    return await response.json();
                }

                static async obtenerImpresorasRemotas(ruta, rutaRemota) {
                    if (ruta) ConectorPlugin.URL_PLUGIN_POR_DEFECTO = ruta;
                    const response = await fetch(ConectorPlugin.URL_PLUGIN_POR_DEFECTO + "/reenviar?host=" +
                        rutaRemota);
                    return await response.json();
                }


                async imprimirEnImpresoraRemota(nombreImpresora, rutaRemota) {
                    const payload = {
                        operaciones: this.operaciones,
                        nombreImpresora,
                        serial: this.serial,
                    };
                    const response = await fetch(this.ruta + "/reenviar?host=" + rutaRemota, {
                        method: "POST",
                        body: JSON.stringify(payload),
                    });
                    return await response.json();
                }

                async imprimirEn(nombreImpresora) {
                    const payload = {
                        operaciones: this.operaciones,
                        nombreImpresora,
                        serial: this.serial,
                    };
                    const response = await fetch(this.ruta + "/imprimir", {
                        method: "POST",
                        // headers: {
                        //    'Content-Type': 'application/json; charset=utf-8'
                        // },
                        body: JSON.stringify(payload),
                    });
                    return await response.json();
                }
            }
            return ConectorPlugin;
        })();
    </script>

    <script>
        var serial = '{{ config('printer.serial') }}';
        var saldoEfectivo = {{ $efectivo ?? 0 }};
        // Leer turno desde el DOM (data-turno) si está disponible, si no usar el valor Blade como fallback
        var turno = 0;
        try {
            var _turnoEl = document.getElementById('turno-label');
            if (_turnoEl && _turnoEl.dataset && _turnoEl.dataset.turno !== undefined) {
                turno = parseInt(_turnoEl.dataset.turno) || parseInt(@json($shift ?? 0));
            } else {
                turno = parseInt(@json($shift ?? 0));
            }
        } catch (e) {
            turno = parseInt(@json($shift ?? 0));
        }
        var isDelivery = false;

        // Función de impresión reutilizable
        async function imprimirCierreCaja() {
            try {
                const IP_COMPUTADORA_REMOTA = "192.168.18.46"; // Cambiar por la IP de tu computadora con impresora
                const PUERTO_REMOTO = "8000";
                const URL_REMOTA = `http://${IP_COMPUTADORA_REMOTA}:${PUERTO_REMOTO}`;

                // Parámetros para ConectorPluginV3
                const licence = serial;
                const conector = new ConectorPluginV3(ConectorPluginV3.URL_PLUGIN_POR_DEFECTO, licence);
                await conector.Iniciar();


                // Función para crear encabezado común
                const crearEncabezado = (conector) => {
                    const ahora = new Date();
                    const fechaFormateada = ahora.getFullYear() + '-' +
                        String(ahora.getMonth() + 1).padStart(2, '0') + '-' +
                        String(ahora.getDate()).padStart(2, '0') + ' ' +
                        String(ahora.getHours()).padStart(2, '0') + ':' +
                        String(ahora.getMinutes()).padStart(2, '0');

                    const fechaInput = document.getElementById('date');
                    const shiftInput = document.getElementById('shift');
                    const userSelect = document.getElementById('user_id');
                    const locationSelect = document.getElementById('location_id');

                    const fecha = fechaInput ? fechaInput.value : '';
                    const turno = shiftInput && shiftInput.selectedOptions && shiftInput.selectedOptions[0]
                        ? shiftInput.selectedOptions[0].text.trim()
                        : '';
                    const usuarioTexto = userSelect && userSelect.selectedOptions && userSelect.selectedOptions[0]
                        ? userSelect.selectedOptions[0].text.trim()
                        : '';
                    const sedeTexto = locationSelect && locationSelect.selectedOptions && locationSelect.selectedOptions[0]
                        ? locationSelect.selectedOptions[0].text.trim()
                        : '';

                    return conector
                        .EstablecerTamañoFuente(1, 1) // Aumentar tamaño de fuente 2x ancho y 2x alto
                        .EstablecerAlineacion(1)
                        .EstablecerEnfatizado(true)
                        .EscribirTexto("My Lady - Cierre de caja\n")
                        .EstablecerAlineacion(0)
                        .EscribirTexto(`Fecha: ${fecha}\n`)
                        .EscribirTexto(`Usuario: ${usuarioTexto}\n`)
                        .EscribirTexto(
                            `Sede: ${sedeTexto}\n`
                        )
                        .EscribirTexto(`Turno: ${turno}\n`)
                        .EscribirTexto(`F. impresion: ${fechaFormateada}\n`)
                        .EscribirTexto(`\n`)
                        .Feed(1)
                        .EscribirTexto("Met.: Total\n")
                        .EscribirTexto(`\n`);
                };

                // Función para agregar productos
                const agregarMontos = (impresionTexto) => {
                    document.querySelectorAll('table.table-bordered tbody tr').forEach(row => {
                        const celdas = row.querySelectorAll('td');
                        if (celdas.length > 0) {
                            let linea = '';
                            celdas.forEach(td => {
                                linea += td.innerText.trim() + '  '; // separador entre columnas
                            });

                            const primerValor = linea.split(" ")[0];

                            // Activar negrita para líneas importantes
                            if (primerValor === "TOTAL" || linea.includes("Efectivo") || primerValor ===
                                "Efectivo") {
                                impresionTexto = impresionTexto.EstablecerEnfatizado(true);
                            } else {
                                impresionTexto = impresionTexto.EstablecerEnfatizado(true);
                            }

                            impresionTexto = impresionTexto.EscribirTexto(linea.slice(0, -1).trim() + '\n');

                            if (primerValor === "TOTAL") {
                                impresionTexto = impresionTexto.EscribirTexto(
                                    "--------------------------------\n");
                            }
                        }
                    });
                    impresionTexto = impresionTexto.EscribirTexto('\n');
                    return impresionTexto;
                };

                // Función para crear pie de documento
                const crearPie = (impresionTexto, efectivo) => {
                    // let textoValidez = "Efectivo = VEN + ANT - EGR";
                    // let real = parseFloat(document.getElementById('amount').value) || 0;
                    // let diferencia = real - efectivo;
                    let textoFinal = "";
                    return impresionTexto
                        .Feed(1)
                        .EstablecerTamañoFuente(1, 1) // Aumentar tamaño de fuente 2x ancho y 2x alto
                        .EstablecerAlineacion(1)
                        .EstablecerEnfatizado(true)
                        // .EscribirTexto(`Real     = S/${real.toFixed(2)}\n`)
                        // .EscribirTexto(`Diferen. = S/${diferencia.toFixed(2)}\n`)
                        .TextoSegunPaginaDeCodigos(2, "cp850", "Elaborado por Xinergia de Corporación XPANDE\n")
                        .Pulso(48, 60, 120)
                        .Corte(1);
                };

                // Función para obtener nombre de impresora según tipo
                const obtenerImpresora = () => {
                    return "Ticketera";
                };

                const imprimirDocumentoAutomatico = async (impresionTexto) => {
                    const nombreImpresora = obtenerImpresora();
                    const tipoDocumentoTexto = "cierre de caja"

                    let resultado = null;
                    let impresionLocal = false;
                    let impresionRemota = false;

                    try {
                        // PASO 1: Intentar impresión local primero
                        console.log('Intentando impresión local...');
                        resultado = await conector.imprimirEn(nombreImpresora);

                        if (resultado && resultado.ok) {
                            impresionLocal = true;
                            console.log('Impresión local exitosa');
                            ToastMessage.fire({
                                text: ` - ${tipoDocumentoTexto} impreso/a localmente`
                            })
                            return; // Salir si la impresión local fue exitosa
                        } else {
                            console.log('Impresión local falló, intentando remota...');
                        }
                    } catch (errorLocal) {
                        console.log('Error en impresión local:', errorLocal.message);
                    }

                    try {
                        // PASO 2: Si falla la impresión local, intentar remota
                        const urlRemotaCompleta = `${URL_REMOTA}/imprimir`;
                        resultado = await conector.imprimirEnImpresoraRemota(nombreImpresora,
                            urlRemotaCompleta);

                        if (resultado && resultado.ok) {
                            impresionRemota = true;
                            console.log('Impresión remota exitosa');
                            ToastMessage.fire({
                                text: ` - ${tipoDocumentoTexto} impreso/a remotamente (fallback)`
                            });
                            return; // Salir si la impresión remota fue exitosa
                        } else {
                            console.log('Impresión remota también falló');
                        }
                    } catch (errorRemoto) {
                        console.log('Error en impresión remota:', errorRemoto.message);
                    }

                    // PASO 3: Si ambas fallaron, mostrar error
                    const mensajeError = resultado && resultado.message ? resultado.message :
                        "No se pudo conectar con ninguna impresora";
                    ToastMessage.fire({
                        icon: 'warning',
                        text: ` - Error al imprimir ${tipoDocumentoTexto}: ${mensajeError}. Se intentó impresión local y remota.`
                    });
                };

                // Procesar según tipo de comprobante
                let impresionTexto;
                let tipoDocumentoCompleto = "cierre de caja";
                let tipoComprobante = "wincha";
                let fecha = document.getElementById('date').value;

                // Crear documento según tipo
                if (tipoComprobante === 'wincha') {
                    impresionTexto = crearEncabezado(conector, fecha);
                    impresionTexto = agregarMontos(impresionTexto);
                    impresionTexto = crearPie(impresionTexto, saldoEfectivo);
                    await imprimirDocumentoAutomatico(impresionTexto);
                } else {
                    // Tipo de comprobante no reconocido
                    ToastMessage.fire({
                        text: ' - Venta registrada sin impresión (tipo no reconocido)'
                    })
                }
            } catch (error) {
                console.error('Error al imprimir:', error);
                ToastMessage.fire({
                    icon: 'error',
                    text: 'Error al imprimir: ' + error.message
                });
            }
        }

        // Event listener para el botón imprimir
        document.getElementById('btnGuardar').addEventListener('click', async (event) => {
            event.preventDefault();
            await imprimirCierreCaja();
        });

        // Formulario de guardar cierre de caja
        $('#store-cash-close-form').on('submit', function(e) {
            e.preventDefault();

            spinner.classList.remove('spinner-visible');
            spinner.classList.add('spinner-hidden');

            let amount = $('#amount').val();
            let date = $('#date').val();

            $.ajax({
                url: "{{ route('cashClose.store') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    amount: amount,
                    date: date
                },
                success: function(response) {
                    if (response.status) {
                        ToastMessage.fire({
                            text: 'Cierre guardado correctamente'
                        });
                        $(`.table-responsive`).removeClass('d-none');
                        // Ejecutar impresión automáticamente después de guardar
                        setTimeout(() => {
                            imprimirCierreCaja();
                        }, 500);
                    } else {
                        ToastMessage.fire({
                            icon: 'error',
                            text: response.error || 'Error al guardar cierre'
                        });
                    }
                },
                error: function(xhr) {
                    ToastMessage.fire({
                        icon: 'error',
                        text: 'Error al guardar cierre'
                    });
                }
            }).always(function() {
                spinner.classList.add('spinner-hidden');
                spinner.classList.remove('spinner-visible');
            });
        });

        $('#date').on('change', function() {
            $('#date-form').submit();
        });
    </script>
    
@endsection

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $pms = PaymentMethod::where('deleted', 0)->get();
        // Solo categorías que tengan productos pertenecientes a la sale_line "RESTAURANTE"
        $pc = Category::where('deleted', 0)->get();
        return view('sales.index');
    }

    public function restaurante()
    {
        //
        $mesas = Table::where('deleted', 0)->get();
        $products = Product::where('deleted', 0)->get();
        $pms = PaymentMethod::where('deleted', 0)->get();
        // Solo categorías que tengan productos pertenecientes a la sale_line "RESTAURANTE"
        $pc = Category::where('deleted', 0)
            ->whereHas('products', function ($q) {
                $q->where('deleted', 0);
            })->get();
        return view('sales.restaurante', compact('pms', 'pc', 'mesas', 'products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {

        //
        $user = Auth::user();

        $locationId = $request->input('location_id', auth()->user()->location_id);

        $products = Product::with('category')
            ->where('deleted', 0)
            ->whereHas('location_prices', function ($q) use ($user) {
                $q->where('location_id', $user->location->id);
            })
            ->get();


        $pms = PaymentMethod::where('deleted', 0)->get();

        $pc = Category::where('deleted', 0)
            ->whereHas('products', function ($q) use ($user) {
                $q->where('deleted', 0)
                    ->whereHas('location_prices', function ($q2) use ($user) {
                        $q2->where('location_id', $user->location->id);
                    });
            })
            ->get();


        return view('sales.create', compact('pms', 'pc', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validaciones básicas antes de la transacción
        $validator = Validator::make($request->all(), [
            'type_status' => 'required|numeric',
            'voucher_type' => 'required|string|in:Boleta,Factura,Ticket',
            'document'     => 'nullable|numeric',
            'client'       => 'nullable|string',
            'telefono'     => 'nullable|string|max:15',
            'sede_recojo'  => 'nullable|integer|exists:headquarters,id',
            'total'        => 'required|numeric',
            'products'     => 'required',
            'monto'        => 'required|array',
            'fecha_entrega' => 'nullable|date',
            'direccion'    => 'nullable|string',
            'referencia'   => 'nullable|string',
            'observacion'  => 'nullable|string',
            'hora_entrega' => 'nullable|string',
            'status' => 'required|numeric',
        ]);


        // Validaciones condicionales
        $validator->sometimes('document', 'nullable|digits:8', function ($r) {
            return $r->voucher_type === 'Boleta';
        });
        $validator->sometimes('document', 'nullable|digits:11', function ($r) {
            return $r->voucher_type === 'Factura';
        });
        $validator->sometimes('client', 'required|string', function ($r) {
            return $r->voucher_type === 'Factura';
        });
        $validator->sometimes('direccion', 'nullable|string', function ($r) {
            return $r->voucher_type === 'Factura';
        });

        if ($validator->fails()) {
            // Solo log de error para validación fallida
            Log::error('Validación fallida en SaleController@store: ' . $validator->errors()->first());

            return response()->json([
                'status' => false,
                'errors'  => $validator->errors()->messages()
            ], 422);
        }

        try {
            $response = DB::transaction(function () use ($request) {

                $documento = $request->document ?? null;
                $cliente_id = null;
                $cliente_nombre = "varios";
                $foto = $request->file('foto');

                if ($documento) {
                    $clienteEncontrado = Client::where('document', $documento)->first();

                    if ($clienteEncontrado) {
                        $cliente_id = $clienteEncontrado->id;
                        $cliente_nombre = $clienteEncontrado->nombre;
                    } else {
                        $nuevoCliente = Client::create([
                            'document' => $documento,
                            'nombre' => $request->client,
                            'estado' => 0
                        ]);
                        $cliente_id = $nuevoCliente->id;
                        $cliente_nombre = $nuevoCliente->nombre;
                    }
                } else {
                    // Si no hay documento pero el usuario ingresó un nombre, usar ese nombre
                    if ($request->client && trim($request->client) !== '') {
                        $cliente_nombre = $request->client;
                    }
                }

                $type_sale = 0;
                $type_status = $request->type_status ?? null;
                $user_id   = auth()->user()->id; // Usar el usuario autenticado
                $status = $request->status ?? null;
                $fecha_entrega = $request->fecha_entrega ?? null;
                $direccion = $request->direccion ?? null;
                $referencia = $request->referencia ?? null;
                $observacion = $request->observacion ?? null;
                $telefono = $request->telefono ?? null;
                $sede_recojo = $request->sede_recojo ?? null;
                $hora_entrega = $request->hora_entrega ?? null;
                $total = floatval($request->total);
                $fecha = now();
                $sede_id = auth()->user()->sede_id ?? null;
                $turno = auth()->user()->turno ?? null;
                $restaurant = $request->restaurant;
                // Normalizar products: aceptar JSON o inputs con keys tipo products[1][cantidad]
                $rawProducts = $request->input('products');
                if (is_string($rawProducts)) {
                    $products = json_decode($rawProducts, true) ?? [];
                } elseif (is_array($rawProducts)) {
                    // Reindex numeric keys (form inputs often come as associative with numeric keys)
                    $products = array_values($rawProducts);
                } else {
                    $products = [];
                }

                // Sanear y unificar claves por cada producto
                $cleanProducts = [];
                foreach ($products as $p) {
                    if (is_array($p) || is_object($p)) {
                        $id = isset($p['id']) ? $p['id'] : (isset($p->id) ? $p->id : null);
                        $cantidad = isset($p['cantidad']) ? $p['cantidad'] : (isset($p->quantity) ? $p->quantity : (isset($p->cantidad) ? $p->cantidad : 0));
                        $precio = isset($p['precio']) ? $p['precio'] : (isset($p->price) ? $p->price : (isset($p->precio) ? $p->precio : 0));
                        if ($id) {
                            $cleanProducts[] = [
                                'id' => $id,
                                'cantidad' => $cantidad,
                                'precio' => $precio,
                            ];
                        }
                    }
                }
                $products = $cleanProducts;

                $location_id = $request->input('location_id');
                $turno = auth()->user()->shift ?? null;

                $venta = Sale::create([
                    'location_id'   => $location_id,
                    'shift'         => $turno,
                    'type_sale'      => $type_sale,
                    'type_status'    => $type_status,
                    'user_id'        => $user_id,
                    'voucher_type'   => $request->voucher_type,
                    'total'          => $total,
                    'date'           => $fecha,
                    'client_id'      => $cliente_id,
                    'client_name'    => $cliente_nombre,
                    'phone'          => $telefono,
                    'delivery_hour'  => $hora_entrega,
                    'delivery_date'  => $fecha_entrega,
                    'address'      => $direccion,
                    'reference'      => $referencia,
                    'observation'    => $observacion,
                    'status'         => $status,
                    'deleted'        => 0,
                ]);

                $sale_id = $venta->id;

                if ($foto != null) {
                    $path = $this->guardarFoto($foto, $sale_id);
                }

                foreach ($request->monto as $metodo_id => $monto) {
                    if ($monto !== null && $monto !== '' && floatval($monto) != 0) {
                        Payment::create([
                            'sale_id'           => $venta->id,
                            'payment_method_id' => $metodo_id,
                            'user_id'           => auth()->user()->id,
                            'location_id'       => auth()->user()->location_id,
                            'shift'             => $turno,
                            'date' => now(),
                            'subtotal'          => floatval($monto),
                            'deleted'           => 0,
                        ]);
                    }
                }
                // Guardar detalles de la venta (todos los productos como individuales)
                foreach ($products as $product) {
                    $id = $product['id'];
                    $cantidad = floatval($product['cantidad']);
                    $precio = floatval($product['precio']);
                    $subtotal = $cantidad * $precio;
                    SaleDetail::create([
                        'product_id' => $id,
                        'sale_id'    => $venta->id,
                        'quantity'   => $cantidad,
                        'unit_price' => $precio,
                        'subtotal'   => $subtotal,
                        'estado'     => 0,
                    ]);
                }

                // REDUCIR STOCK: Solo para ventas normales (type_sale = 0), no para anticipadas
                if ($type_sale == 0) {
                    foreach ($products as $product) {
                        $this->reducirStockProducto($product['id'], floatval($product['cantidad']), $sede_id);
                    }
                }

                // Si es Boleta o Factura, enviamos a SUNAT
                $pdf_url = null;
                $detraction_text = null;
                // En tu método store, después de crear la venta:
                if (in_array($request->voucher_type, ['Boleta', 'Factura'])) {
                    $sunatResponse = $this->sendInvoice($venta);

                    if (!$sunatResponse['status']) {
                        throw new \Exception('Error al enviar a SUNAT: ' . $sunatResponse['console']);
                    }

                    $pdf_url = $sunatResponse['pdf'];
                    $detraction_text = $sunatResponse['detraction_text'];
                } elseif ($request->voucher_type === 'Ticket') {
                    // Generar número correlativo interno para Ticket
                    $numeroInterno = $this->generarNumeroTicket();
                    $venta->update(['number' => $numeroInterno]);

                    // No hay PDF ni texto de detracción para Ticket
                    $pdf_url = null;
                    $detraction_text = null;
                }

                // ...dentro del método store...
                $metodos_pago = [];
                foreach ($request->monto as $metodo_id => $monto) {
                    if ($monto !== null && $monto !== '' && floatval($monto) != 0) {
                        $metodo = PaymentMethod::find($metodo_id);
                        $nombreMetodo = $metodo ? $metodo->nombre : 'Método';
                        $metodos_pago[] = [
                            'nombre' => $nombreMetodo,
                            'monto'  => floatval($monto),
                        ];
                    }
                }

                // Cargar la relación del usuario para la respuesta
                $venta->load('usuario');

                // Respuesta exitosa
                return response()->json([
                    'status'  => true,
                    'message' => 'Venta registrada correctamente.',
                    'sale_id' => $venta->id,
                    'venta'   => [
                        'id'            => $venta->id,
                        'user_id'       => $venta->user_id,
                        'usuario'       => $venta->usuario, // Incluir toda la información del usuario
                        'number'        => $venta->number,
                        'cliente'       => $cliente_nombre,
                        'documento'     => $documento ?? '-',
                        'fecha'         => $fecha,
                        'fecha_entrega' => $fecha_entrega ?? '-',
                        'direccion'     => $direccion ?? '-',
                        'productos'     => $products,
                        'total'         => $total,
                        'metodos_pago'  => $metodos_pago, // <-- aquí el array correcto
                        'pagado'        => collect($request->monto)->sum(),
                    ],
                    'pdf'            => $pdf_url,
                    'detraction_text' => $detraction_text,
                ], 201);
            });

            return $response;
        } catch (\Throwable $e) {
            Log::error('❌ Error en store(): ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'error'  => 'Error al registrar venta: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function generarNumeroTicket()
    {
        // Usa transacción para evitar conflictos en concurrencia
        return DB::transaction(function () {
            // Bloquea la fila para actualizar el número
            $registro = DB::table('correlativos')->where('tipo', 'Ticket')->lockForUpdate()->first();

            if (!$registro) {
                // Si no existe registro, crea uno
                DB::table('correlativos')->insert([
                    'tipo' => 'Ticket',
                    'numero' => 1
                ]);
                return 'TICKET-00000001';
            }

            $nuevoNumero = $registro->numero + 1;

            DB::table('correlativos')
                ->where('tipo', 'Ticket')
                ->update(['numero' => $nuevoNumero]);

            // Formatea el número con ceros a la izquierda y prefijo
            return 'TICKET-' . str_pad($nuevoNumero, 8, '0', STR_PAD_LEFT);
        });
    }


    public function sendInvoice(Sale $sale)
    {
        $url = config('apisunat.url') . '/personas/lastDocument';
        $personaId = config('apisunat.id');
        $personaToken = config('apisunat.token.prod');

        $catalog = [
            'Boleta' => [
                'InvoiceTypeCode' => '03',
                'PartyIdentification' => '1',
                'serie' => 'B001'
            ],
            'Factura' => [
                'InvoiceTypeCode' => '01',
                'PartyIdentification' => '6',
                'serie' => 'F001'
            ]
        ];

        if (!isset($catalog[$sale->voucher_type])) {
            return [
                'status' => false,
                'console' => 'Tipo de comprobante no soportado para envío a SUNAT.'
            ];
        }

        $cat = $catalog[$sale->voucher_type];

        // Datos del emisor (tu empresa)
        $company = $this->getCompanyProfile();
        $ruc = $company['ruc'];
        $name = $company['name'];
        $address = $company['address'];

        $client = optional($sale->client);

        $type = $cat['InvoiceTypeCode'];
        $serie = $cat['serie'];

        // Consultar último correlativo SUNAT
        $respUltimo = Http::post($url, [
            'personaId' => $personaId,
            'personaToken' => $personaToken,
            'type' => $type,
            'serie' => $serie
        ]);

        if ($respUltimo->failed()) {
            return [
                'status' => false,
                'console' => 'Error al consultar último correlativo: ' . $respUltimo->body()
            ];
        }

        $responseObj = $respUltimo->object();
        $number = trim($responseObj->suggestedNumber ?? '');

        if (!$number || !is_numeric($number)) {
            return [
                'status' => false,
                'console' => 'No se recibió correlativo válido desde SUNAT.'
            ];
        }

        $number = str_pad($number, 8, "0", STR_PAD_LEFT);

        // Cálculo de montos
        $total = round(floatval($sale->total), 2);
        $subtotal = round($total / 1.18, 2); // IGV 18% en Perú
        $igv = round($total - $subtotal, 2);

        $data = [
            'personaId' => $personaId,
            'personaToken' => $personaToken,
            'fileName' => "{$ruc}-{$type}-{$serie}-{$number}",
            'documentBody' => [
                'cbc:UBLVersionID' => ['_text' => '2.1'],
                'cbc:CustomizationID' => ['_text' => '2.0'],
                'cbc:ID' => ['_text' => "{$serie}-{$number}"],
                'cbc:IssueDate' => [
                    '_text' => now()->format('Y-m-d')
                ],
                'cbc:IssueTime' => [
                    '_text' => now()->format('H:i:s')
                ],
                'cbc:InvoiceTypeCode' => [
                    '_attributes' => ['listID' => '0101'],
                    '_text' => $type
                ],
                'cbc:Note' => [],
                'cbc:DocumentCurrencyCode' => ['_text' => 'PEN'],
                'cac:AccountingSupplierParty' => [
                    'cac:Party' => [
                        'cac:PartyIdentification' => [
                            'cbc:ID' => [
                                '_attributes' => ['schemeID' => '6'],
                                '_text' => $ruc
                            ]
                        ],
                        'cac:PartyLegalEntity' => [
                            'cbc:RegistrationName' => ['_text' => $name],
                            'cac:RegistrationAddress' => [
                                'cbc:AddressTypeCode' => ['_text' => '0000'],
                                'cac:AddressLine' => ['cbc:Line' => ['_text' => $address]]
                            ]
                        ]
                    ]
                ],
                'cac:AccountingCustomerParty' => [
                    'cac:Party' => [
                        'cac:PartyIdentification' => [
                            'cbc:ID' => [
                                '_attributes' => ['schemeID' => $cat['PartyIdentification']],
                                '_text' => $client->ruc_dni ?? '00000000'
                            ]
                        ],
                        'cac:PartyLegalEntity' => [
                            'cbc:RegistrationName' => ['_text' => $client->business_name ?? 'CLIENTE VARIOS']
                        ]
                    ]
                ],
                'cac:TaxTotal' => [
                    'cbc:TaxAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $igv
                    ],
                    'cac:TaxSubtotal' => [
                        'cbc:TaxableAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $subtotal
                        ],
                        'cbc:TaxAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $igv
                        ],
                        'cac:TaxCategory' => [
                            'cac:TaxScheme' => [
                                'cbc:ID' => ['_text' => '1000'],
                                'cbc:Name' => ['_text' => 'IGV'],
                                'cbc:TaxTypeCode' => ['_text' => 'VAT']
                            ]
                        ]
                    ]
                ],
                'cac:LegalMonetaryTotal' => [
                    'cbc:LineExtensionAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $subtotal
                    ],
                    'cbc:TaxInclusiveAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $total
                    ],
                    'cbc:AllowanceTotalAmount' => [],
                    'cbc:PayableAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $total
                    ]
                ],
                'cac:InvoiceLine' => [],
            ]
        ];

        // Manejo de términos de pago para Facturas
        if ($sale->voucher_type == 'Factura') {
            // Siempre establecer como "Contado"
            $data['documentBody']['cac:PaymentTerms'] = [[
                "cbc:ID" => ["_text" => "FormaPago"],
                "cbc:PaymentMeansID" => ["_text" => "Contado"]
            ]];
        }

        // Detracción para factura > S/700
        $detraction_text = '';
        if ($sale->voucher_type == 'Factura' && $total >= 700) {
            $detraction = round($total * 0.12, 2);
            $detraction_text = "Detracción: Nro. Cta. Banco de la Nación: 00-250-053223, Porcentaje: 12.00, Monto: S/{$detraction}";

            $data['documentBody']['cbc:InvoiceTypeCode']['_attributes']['listID'] = '1001';
            $data['documentBody']['cbc:Note'][] = [
                '_text' => 'OPERACIÓN SUJETA A DETRACCIÓN',
                '_attributes' => ['languageLocaleID' => '2006']
            ];
            $data['documentBody']['cac:PaymentTerms'][] = [
                'cbc:ID' => ['_text' => 'Detraccion'],
                'cbc:PaymentMeansID' => ['_text' => '022'],
                'cbc:PaymentPercent' => ['_text' => '12'],
                'cbc:Amount' => [
                    '_attributes' => ['currencyID' => 'PEN'],
                    '_text' => $detraction
                ]
            ];
            $data['documentBody']['cac:PaymentMeans'][] = [
                'cbc:ID' => ['_text' => 'Detraccion'],
                'cbc:PaymentMeansCode' => ['_text' => '001'],
                'cac:PayeeFinancialAccount' => [
                    'cbc:ID' => ['_text' => '00250053223']
                ]
            ];
        }

        // Detalle de productos (InvoiceLine) - Adaptado a tu estructura
        $details = $sale->details()->where('unit_price', '>', 0)->get();

        if ($details->isEmpty()) {
            // Si no hay detalles específicos, crear una línea general
            $data['documentBody']['cac:InvoiceLine'][] = [
                'cbc:ID' => ['_text' => 1],
                'cbc:InvoicedQuantity' => [
                    '_attributes' => ['unitCode' => 'NIU'],
                    '_text' => 1
                ],
                'cbc:LineExtensionAmount' => [
                    '_attributes' => ['currencyID' => 'PEN'],
                    '_text' => $subtotal
                ],
                'cac:PricingReference' => [
                    'cac:AlternativeConditionPrice' => [
                        'cbc:PriceAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $total
                        ],
                        'cbc:PriceTypeCode' => ['_text' => '01']
                    ]
                ],
                'cac:TaxTotal' => [
                    'cbc:TaxAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $igv
                    ],
                    'cac:TaxSubtotal' => [
                        'cbc:TaxableAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $subtotal
                        ],
                        'cbc:TaxAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $igv
                        ],
                        'cac:TaxCategory' => [
                            'cbc:Percent' => ['_text' => 18],
                            'cbc:TaxExemptionReasonCode' => ['_text' => '10'],
                            'cac:TaxScheme' => [
                                'cbc:ID' => ['_text' => '1000'],
                                'cbc:Name' => ['_text' => 'IGV'],
                                'cbc:TaxTypeCode' => ['_text' => 'VAT']
                            ]
                        ]
                    ]
                ],
                'cac:Item' => [
                    'cbc:Description' => ['_text' => 'Venta general']
                ],
                'cac:Price' => [
                    'cbc:PriceAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $subtotal
                    ]
                ]
            ];
        } else {
            // Usar los detalles específicos de la venta
            $i = 1;
            foreach ($details as $detail) {
                $price = round($detail->unit_price, 2);
                $cost = round($price / 1.18, 2); // Precio sin IGV
                $quantity = $detail->quantity;
                $totalLine = round($price * $quantity, 2);
                $subtotalLine = round($totalLine / 1.18, 2);
                $igvLine = round($totalLine - $subtotalLine, 2);

                $data['documentBody']['cac:InvoiceLine'][] = [
                    'cbc:ID' => ['_text' => $i],
                    'cbc:InvoicedQuantity' => [
                        '_attributes' => ['unitCode' => 'NIU'],
                        '_text' => $quantity
                    ],
                    'cbc:LineExtensionAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $subtotalLine
                    ],
                    'cac:PricingReference' => [
                        'cac:AlternativeConditionPrice' => [
                            'cbc:PriceAmount' => [
                                '_attributes' => ['currencyID' => 'PEN'],
                                '_text' => $price
                            ],
                            'cbc:PriceTypeCode' => ['_text' => '01']
                        ]
                    ],
                    'cac:TaxTotal' => [
                        'cbc:TaxAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $igvLine
                        ],
                        'cac:TaxSubtotal' => [
                            [
                                'cbc:TaxableAmount' => [
                                    '_attributes' => ['currencyID' => 'PEN'],
                                    '_text' => $subtotalLine
                                ],
                                'cbc:TaxAmount' => [
                                    '_attributes' => ['currencyID' => 'PEN'],
                                    '_text' => $igvLine
                                ],
                                'cac:TaxCategory' => [
                                    'cbc:Percent' => ['_text' => 18],
                                    'cbc:TaxExemptionReasonCode' => ['_text' => '10'],
                                    'cac:TaxScheme' => [
                                        'cbc:ID' => ['_text' => '1000'],
                                        'cbc:Name' => ['_text' => 'IGV'],
                                        'cbc:TaxTypeCode' => ['_text' => 'VAT']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'cac:Item' => [
                        'cbc:Description' => ['_text' => optional($detail->product)->name ?? 'Producto']
                    ],
                    'cac:Price' => [
                        'cbc:PriceAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $cost
                        ]
                    ]
                ];

                $i++;
            }
        }

        // Enviar a SUNAT
        $urlSend = config('apisunat.url') . '/personas/v1/sendBill';
        $source = Http::post($urlSend, $data);
        $response = $source->object();

        if ($source->failed()) {
            return [
                'status' => false,
                'console' => $response->error->message ?? 'Error desconocido al enviar a SUNAT'
            ];
        }

        $documentId = $response->documentId;
        $filename = "{$ruc}-{$type}-{$serie}-{$number}";

        $url = config('apisunat.url') . "/documents/{$documentId}/getPDF/ticket80mm/{$filename}.pdf";

        // Actualizar la venta con los datos de SUNAT
        $sale->update([
            'voucher_id' => $documentId,
            'voucher_file' => $filename . '.pdf',
            'number' => "{$serie}-{$number}"
        ]);

        return [
            'status' => true,
            'pdf' => $url,
            'detraction_text' => $detraction_text
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function pdfA4($id)
    {
        try {
            $sale = Sale::with([
                'client',
                'details.product',
                'location',
                'payments.paymentMethod',
                'usuario',
            ])->findOrFail($id);

            if (!in_array($sale->voucher_type, ['Boleta', 'Factura'])) {
                return response()->json([
                    'status' => false,
                    'error' => 'El PDF A4 solo está disponible para boletas y facturas.',
                ], 422);
            }

            $client = $sale->client;
            $voucherType = $sale->voucher_type;
            $seriesNumber = $sale->number ?: ($voucherType === 'Factura' ? 'F001-00000000' : 'B001-00000000');
            $company = $this->getCompanyProfile();
            $companyName = $company['name'];
            $companyAddress = $company['address'];
            $companyAddressLines = $company['address_lines'];
            $companyRuc = $company['ruc'];

            $clientName = $client->business_name
                ?? $client->name
                ?? $client->contact_name
                ?? $sale->client_name
                ?? 'CLIENTE VARIOS';

            $clientDocument = $client->document
                ?? ($voucherType === 'Factura' ? '00000000000' : '00000000');

            $details = $sale->details
                ->filter(function ($detail) {
                    return (float) $detail->unit_price > 0;
                })
                ->values();

            $total = round((float) $sale->total, 2);
            $subtotal = round($total / 1.18, 2);
            $igv = round($total - $subtotal, 2);
            $issueDate = $sale->date ? \Carbon\Carbon::parse($sale->date) : now();
            $detraction = null;

            if ($voucherType === 'Factura' && $total >= 700) {
                $detraction = round($total * 0.12, 2);
            }

            $qrPayload = $this->buildQrPayload($companyRuc, $voucherType, $seriesNumber, $issueDate, $clientDocument, $total, $igv);
            $qrDataUri = $this->generateQrDataUri($qrPayload);

            $logoDataUri = $this->getLogoDataUri();

            $pdf = Pdf::loadView('sales.pdf.a4', compact(
                'sale',
                'clientName',
                'clientDocument',
                'voucherType',
                'seriesNumber',
                'companyName',
                'companyAddress',
                'companyAddressLines',
                'companyRuc',
                'details',
                'total',
                'subtotal',
                'igv',
                'issueDate',
                'detraction',
                'logoDataUri',
                'qrPayload',
                'qrDataUri'
            ))->setPaper('A4', 'portrait');

            $filename = strtolower(str_replace(' ', '_', $voucherType . '_' . $seriesNumber . '.pdf'));

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al generar PDF A4 interno: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'error' => 'Error al generar el PDF A4: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getCompanyProfile(): array
    {
        $addressLines = [
            'AV. JOSE BALTA NRO. 054 P.J. CHINO ZAMORA CHICLAYO',
            'CHICLAYO LAMBAYEQUE',
        ];

        return [
            'ruc' => config('ruc.number'),
            'name' => 'MUSAS PASTELERIA S.R.L.',
            'address' => implode(' ', $addressLines),
            'address_lines' => $addressLines,
        ];
    }

    private function getLogoDataUri(): ?string
    {
        $candidates = [
            ['path' => base_path('assets/icon/xinergia.jpeg'), 'mime' => 'image/jpeg'],
            ['path' => base_path('assets/icon/xinergia.jpg'), 'mime' => 'image/jpeg'],
            ['path' => base_path('assets/icon/logo.svg'), 'mime' => 'image/svg+xml'],
            ['path' => public_path('assets/icon/xinergia.jpeg'), 'mime' => 'image/jpeg'],
            ['path' => public_path('assets/icon/xinergia.jpg'), 'mime' => 'image/jpeg'],
            ['path' => public_path('assets/icon/logo.svg'), 'mime' => 'image/svg+xml'],
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate['path'])) {
                return 'data:' . $candidate['mime'] . ';base64,' . base64_encode(file_get_contents($candidate['path']));
            }
        }

        return null;
    }

    private function buildQrPayload(string $companyRuc, string $voucherType, string $seriesNumber, $issueDate, string $clientDocument, float $total, float $igv): string
    {
        $typeCode = $voucherType === 'Factura' ? '01' : '03';
        $docType = $voucherType === 'Factura' ? '6' : '1';

        return implode('|', [
            $companyRuc,
            $typeCode,
            $seriesNumber,
            number_format($igv, 2, '.', ''),
            number_format($total, 2, '.', ''),
            $issueDate->format('Y-m-d'),
            $docType,
            $clientDocument,
        ]);
    }

    private function generateQrDataUri(string $payload): string
    {
        $result = Builder::create()
            ->writer(new SvgWriter())
            ->writerOptions([])
            ->validateResult(false)
            ->data($payload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(220)
            ->margin(0)
            ->build();

        return $result->getDataUri();
    }

    // ...existing code...
    public function historic(Request $request)
    {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $numero_comprobante = $request->input('number');
        $client_name = $request->input('client_name');
        $client_id = $request->input('client_id');
        $voucher_type = $request->input('voucher_type');
        $payment_method_id = $request->input('payment_method_id');
        $shift = $request->input('shift');
        $location_id = $request->input('location_id');
        
        $client = Client::find($client_id);
        if ($client) {
            $request->merge(['client_name' => $client->business_name ? $client->business_name : $client->contact_name]);
        }

        $paymentMethod = PaymentMethod::where('deleted', 0)->get();
        $user = Auth::user();
        $roleName = strtolower(optional($user->rol)->name ?? '');

        $consulta = Sale::query()->where('deleted', 0)
            ->when($start_date, fn($q) => $q->whereDate('date', '>=', $start_date))
            ->when($end_date, fn($q) => $q->whereDate('date', '<=', $end_date))
            ->when($numero_comprobante, fn($q) => $q->where('number', 'like', "%$numero_comprobante%"))
            ->when($shift !== null && $shift !== '', fn($q) => $q->where('shift', $shift))
            ->when($client_id, fn($q) => $q->where('client_id', $client_id))
            ->when($voucher_type, fn($q) => $q->where('voucher_type', $voucher_type))
            ->when($payment_method_id, function ($q) use ($payment_method_id) {
                $q->whereHas('payments', fn($q2) => $q2->where('payment_method_id', $payment_method_id));
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($roleName === 'caja') {
            $consulta->where('location_id', $user->location_id);
        } elseif ($roleName !== 'admin' && $roleName !== 'administrador' && $roleName !== 'contabilidad') {
            $consulta->where('user_id', $user->id);
        }

        // Si es rol contabilidad, excluir las ventas tipo TICKET
        if ($roleName === 'contabilidad') {
            $consulta->where('voucher_type', '!=', 'TICKET');
        }

        // Si es admin/contabilidad y filtró por sede
        if (($roleName === 'admin' || $roleName === 'administrador' || $roleName === 'contabilidad') && $location_id) {
            $consulta->where('location_id', $location_id);
        }

        $total = $consulta->sum('total');

        $total_pagos = Payment::query()
            ->where('deleted', 0)
            ->when($start_date, fn($q) => $q->whereDate('date', '>=', $start_date))
            ->when($end_date, fn($q) => $q->whereDate('date', '<=', $end_date))
            ->when($payment_method_id, fn($q) => $q->where('payment_method_id', $payment_method_id))
            ->whereHas('sale', function ($q) use ($numero_comprobante, $client_id, $voucher_type, $user, $shift, $roleName, $location_id) {
                if ($roleName === 'caja') {
                    $q->where('location_id', $user->location_id);
                } elseif ($roleName !== 'admin' && $roleName !== 'administrador' && $roleName !== 'contabilidad') {
                    $q->where('user_id', $user->id);
                }

                // Si es contabilidad, excluir TICKET
                if ($roleName === 'contabilidad') {
                    $q->where('voucher_type', '!=', 'TICKET');
                }

                // Si es admin/contabilidad y filtró por sede
                if (($roleName === 'admin' || $roleName === 'administrador' || $roleName === 'contabilidad') && $location_id) {
                    $q->where('location_id', $location_id);
                }

                $q->when($numero_comprobante, fn($q2) => $q2->where('number', 'like', "%$numero_comprobante%"))
                ->when($client_id, fn($q2) => $q2->where('client_id', $client_id))
                ->when($shift !== null && $shift !== '', fn($q2) => $q2->where('shift', $shift))
                ->when($voucher_type, fn($q2) => $q2->where('voucher_type', $voucher_type));
            })
            ->sum('subtotal');
        $anticipadas = $consulta->paginate(15);
        $anticipadas->appends($request->all());

        $locations = null;
        $allowed_locations_for_accounting = [1, 2];
        if ($roleName === 'admin') {
            $locations = Location::where('deleted', 0)->get();
        } elseif ($roleName === 'contabilidad') {
            $locations = Location::where('deleted', 0)
                ->whereIn('id', $allowed_locations_for_accounting)
                ->get();
        }

        return view('sales.historic', compact(
            'anticipadas',
            'start_date',
            'end_date',
            'paymentMethod',
            'voucher_type',
            'total',
            'total_pagos',
            'payment_method_id',
            'locations'
        ));
    }

    public function getProductsByCategory(Request $request, $categoryId)
    {
        $user = auth()->user();
        $products = Product::where('category_id', $categoryId)
            ->where('products.deleted', 0)
            ->whereHas('location_prices', function ($q) use ($user) {
                $q->where('location_id', $user->location->id);
            })
            ->join('location_prices', function ($join) use ($user) {
                $join->on('products.id', '=', 'location_prices.product_id')
                     ->where('location_prices.location_id', $user->location->id);
            })
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.id', 'products.name', 'location_prices.price as unit_price', 'location_prices.quantity as stock', 'categories.name as category')
            ->get();

        return response()->json($products);
    }

    /**
     * Get all products grouped by category for AJAX requests
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllProducts(Request $request)
    {
        $user = auth()->user();
        $products = Product::with('category')
            ->where('products.deleted', 0)
            ->whereHas('location_prices', function ($q) use ($user) {
                $q->where('location_id', $user->location->id);
            })
            ->join('location_prices', function ($join) use ($user) {
                $join->on('products.id', '=', 'location_prices.product_id')
                     ->where('location_prices.location_id', $user->location->id);
            })
            ->select('products.id', 'products.name', 'location_prices.price as unit_price', 'location_prices.quantity as stock', 'products.category_id')
            ->get()
            ->groupBy('category_id');

        return response()->json($products);
    }

    /**
     * Guardar foto de la venta
     *
     * @param  \Illuminate\Http\UploadedFile  $foto
     * @param  int  $saleId
     * @return string
     */
    private function guardarFoto($foto, $saleId)
    {
        try {
            $nombreArchivo = 'venta_' . $saleId . '_' . time() . '.' . $foto->getClientOriginalExtension();
            $rutaDestino = public_path('uploads/ventas/');

            // Crear directorio si no existe
            if (!file_exists($rutaDestino)) {
                mkdir($rutaDestino, 0777, true);
            }

            $foto->move($rutaDestino, $nombreArchivo);

            $rutaCompleta = 'uploads/ventas/' . $nombreArchivo;

            // Actualizar la venta con la ruta de la foto
            Sale::where('id', $saleId)->update(['foto' => $rutaCompleta]);

            return $rutaCompleta;
        } catch (\Exception $e) {
            Log::error('Error al guardar foto de venta: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Reducir stock de un producto
     *
     * @param  int  $productId
     * @param  float  $quantity
     * @param  int  $sedeId
     * @return void
     */
    private function reducirStockProducto($productId, $quantity, $sedeId = null)
    {
        try {
            $product = Product::find($productId);
            if ($product) {
                // Reducir el stock general del producto
                $newStock = $product->quantity - $quantity;
                $product->update(['quantity' => max(0, $newStock)]);

                Log::info("Stock reducido para producto ID {$productId}: -{$quantity}. Stock actual: {$newStock}");
            }
        } catch (\Exception $e) {
            Log::error("Error al reducir stock del producto {$productId}: " . $e->getMessage());
        }
    }

    public function consultarSunat(Request $request)
    {
        $doc = $request->query('doc');

        if (!$doc || (strlen($doc) !== 8 && strlen($doc) !== 11)) {
            return response()->json([
                'success' => false,
                'message' => 'Documento inválido'
            ], 422);
        }

        $urlBase = config('apisunat.url');
        $personaId = config('apisunat.id');
        $personaToken = config('apisunat.token.prod');

        try {
            if (strlen($doc) === 8) {
                $url = "$urlBase/personas/$personaId/getDNI?dni=$doc&personaToken=$personaToken";
            } else {
                $url = "$urlBase/personas/$personaId/getRUC?ruc=$doc&personaToken=$personaToken";
            }

            $response = Http::get($url);

            // ✅ LOG TEMPORAL
            Log::info('Consulta a API Sunat/Reniec', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('data')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener información de SUNAT/RENIEC'
                ], $response->status());
            }
        } catch (\Exception $e) {
            // ✅ LOG ERROR
            Log::error('Error al consultar Sunat', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function confirmarPedido(Request $request)
    {
        try {
            $order_id = $request->order_id;
            $order = Order::where('id', $order_id)
                ->firstOrFail();
            $not_confirmed = $order->orderdetails()
                ->with('product')
                ->where('confirmado', 0) // Solo detalles no confirmados
                ->get();

            //Updatear productos confirmados y orden
            $order->orderdetails()
                ->where('confirmado', 0)
                ->update(['confirmado' => 1]);

            return response()->json([
                'success' => true,
                'status' => true,
                'table' => $order->table->name,
                'order_id' => $order->id,
                'details' => $not_confirmed->count() > 0 ? $not_confirmed : null
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cerrar mesa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al confirmar pedidos.']);
        }
    }

    public function precuenta(Request $request)
    {
        try {
            $order_id = $request->order_id;
            $order = Order::where('id', $order_id)
                ->firstOrFail();

            $details = $order->orderdetails()
                ->with('product')
                ->get();

            return response()->json([
                'success' => true,
                'status' => true,
                'table' => $order->table->name,
                'order_id' => $order->id,
                'details' => $details->count() > 0 ? $details : null
            ]);
        } catch (\Exception $e) {
            Log::error('Error al generar precuenta: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al generar precuenta.']);
        }
    }

    public function abrirMesa($id)
    {
        Log::info('AbrirMesa - Inicio', ['mesa_id' => $id]);

        $mesa = Table::with(['order.order_details.product'])->findOrFail($id);

        Log::info('AbrirMesa - Mesa encontrada', [
            'mesa' => $mesa->toArray(),
            'has_order' => $mesa->order ? true : false,
            'order_details_count' => $mesa->order && $mesa->order->order_details ? $mesa->order->order_details->count() : 0
        ]);

        if ($mesa->status === 'Libre') {
            $mesa->update([
                'status' => 'Ocupado',
                'opened_at' => now(),
            ]);

            $order = Order::create([
                'table_id' => $mesa->id,
                'status' => 'Abierto'
            ]);

            $productos = [];
        } else {
            // Para mesas ocupadas, buscar la orden activa
            $order = $mesa->order;

            // Si no existe orden, crear una nueva (caso edge)
            if (!$order) {
                $order = Order::create([
                    'table_id' => $mesa->id,
                    'status' => 'Abierto'
                ]);
                $productos = [];
            } else {
                // Cargar productos existentes si hay una orden
                $productos = [];
                if ($order->order_details && $order->order_details->count() > 0) {
                    Log::info('OrderDetails encontrados', [
                        'count' => $order->order_details->count(),
                        'detalles' => $order->order_details->toArray()
                    ]);

                    $productos = $order->order_details->map(function ($detalle) {
                        Log::info('Procesando detalle', [
                            'detalle_raw' => $detalle->toArray(),
                            'product' => $detalle->product ? $detalle->product->toArray() : null
                        ]);

                        // Usar nombres exactos de la base de datos
                        $nombre = ($detalle->product_id == 238)
                            ? 'Producto Personalizado'  // Para casos especiales, usar nombre genérico
                            : ($detalle->product ? $detalle->product->name : 'Producto');

                        $producto_mapeado = [
                            'id'         => $detalle->product_id,
                            'nombre'     => $nombre,
                            'cantidad'   => $detalle->quantity,        // Campo exacto de la DB
                            'precio'     => $detalle->product_price,   // Campo exacto de la DB
                            'confirmado' => $detalle->confirmed,       // Campo exacto de la DB
                            'stock'      => $detalle->product ? $detalle->product->quantity : 9999
                        ];

                        Log::info('Producto mapeado', $producto_mapeado);
                        return $producto_mapeado;
                    })->toArray();
                }
            }
        }

        Log::info('AbrirMesa - Respuesta', [
            'mesa_id' => $mesa->id,
            'order_id' => $order->id,
            'productos_count' => count($productos),
            'productos' => $productos
        ]);

        return response()->json([
            'success' => true,
            'mesa_id' => $mesa->id,
            'opened_at' => $mesa->opened_at,
            'order_id' => $order->id ?? null,
            'productos' => $productos,
            'mesa' => [
                'id' => $mesa->id,
                'name' => $mesa->name,
                'status' => $mesa->status
            ]
        ]);
    }

    public function verPedido($id)
    {
        $mesa = Table::with(['order.order_details.product'])->findOrFail($id);

        if (!$mesa->order) {
            return response()->json([
                'success' => false,
                'message' => 'No hay pedido abierto para esta mesa.'
            ], 404);
        }

        $productos = $mesa->order->order_details->map(function ($detalle) {
            $nombre = ($detalle->product_id == 238)
                ? 'Producto Personalizado'
                : ($detalle->product ? $detalle->product->name : 'Producto');

            return [
                'id'         => $detalle->product_id,
                'nombre'     => $nombre,
                'cantidad'   => $detalle->quantity,      // Corregido
                'precio'     => $detalle->product_price, // Corregido
                'confirmado' => $detalle->confirmed,     // Corregido
                'stock'      => $detalle->product ? $detalle->product->quantity : 9999
            ];
        });

        Log::info('Pedido cargado', [
            'mesa_id' => $id,
            'productos' => $productos
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $mesa->order->id,
            'productos' => $productos
        ]);
    }

    public function cerrarMesa($id)
    {
        try {
            $mesa = Table::with('order.order_details')->findOrFail($id);

            if ($mesa->order) {
                // Eliminar detalles
                $mesa->order->order_details()->delete();

                // Eliminar la orden
                $mesa->order()->delete();
            }

            // Liberar mesa
            $mesa->update([
                'status' => 'libre',
                'opened_at' => null,
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error al cerrar mesa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al cerrar la mesa.']);
        }
    }

    public function addProductToOrder(Request $request, $orderId)
    {
        try {
            $validated = $request->validate([
                'product_id'      => 'required|integer|exists:products,id',
                'quantity'        => 'required|numeric|min:0',
                'product_price' => 'required|numeric|min:0',
            ]);

            $order = Order::findOrFail($orderId);
            // Usar key simple: order_id + product_id
            $key = [
                'order_id'   => $orderId,
                'product_id' => (int) $validated['product_id'],
            ];

            // Buscar detalle existente
            $detail = OrderDetail::where($key)->first();

            $cantidadNueva  = (float) $validated['quantity'];
            $precioUnitario = (float) $validated['product_price'];
            $sumar          = $request->boolean('sumar'); // true cuando es click en botón

            $nombreOpt = $request->input('nombre', null);

            if ($sumar) {
                // SUMAR cantidad (clicks de botón)
                if ($detail) {
                    $detail->cantidad        = (float) $detail->cantidad + $cantidadNueva;
                    $detail->precio_unitario = $precioUnitario; // actualizar PU si necesario
                    // Actualizar nombre si viene en la request
                    if (!empty($nombreOpt)) {
                        $detail->nombre = $nombreOpt;
                    }
                    $detail->save();
                } else {
                    $detail = OrderDetail::create([
                        'order_id'        => $orderId,
                        'product_id'      => (int) $validated['product_id'],
                        'quantity'        => $cantidadNueva,
                        'product_price' => $precioUnitario,
                    ]);
                }
            } else {
                // SOBREESCRIBIR cantidad (edición desde el input)
                $detail = OrderDetail::updateOrCreate(
                    $key,
                    [
                        'quantity'        => $cantidadNueva,
                        'product_price' => $precioUnitario,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => $sumar ? 'Cantidad sumada correctamente' : 'Producto actualizado correctamente',
                'data'    => $detail,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al agregar producto al pedido: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getVoucherData(Request $request)
    {
        try {

            $voucher_id = $request->voucher_id;
            $type = $request->type;

            // cdr solo da en producción! en dev no
            if (!in_array($type, ['xml', 'cdr'])) { //si no es xml ni cdr que lance error
                return response()->json(['status' => false, 'message' => 'Type incorrecto']);
            }

            $response = $this->getInvoiceById($voucher_id);
            $data = $response->getData(true);

            // Manejo de error
            if (isset($data['status']) && $data['status'] === false) {
                return response()->json(['status' => false, 'error' => $data['error'] ?? 'Error desconocido']);
            }

            // Excepción para CDR no disponible
            if ($type === 'cdr' && (empty($data['data']['cdr']) || !filter_var($data['data']['cdr'], FILTER_VALIDATE_URL))) {
                return response()->json([
                    'status' => false,
                    'error' => 'El CDR solo estara disponible cuando el comprobante sea aceptado por SUNAT.'
                ])->header('Content-Type', 'application/json; charset=UTF-8');
            }


            return redirect()->away($data['data'][$type]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'error' => 'Error al obtener información del comprobante: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function anular(Request $request)
    {
        try {
            $sale_id = $request->sale_id;

            // 1. Buscar la venta
            $venta = Sale::findOrFail($sale_id);

            if ($venta->deleted !== 0) {
                return response()->json([
                    'status' => false,
                    'error' => 'La venta ya fue anulada anteriormente.'
                ]);
            }

            DB::transaction(function () use ($venta) {
                // 2. Cambiar estado en tabla SALES
                $venta->deleted = 1;
                $venta->save();

                // 3. Cambiar estado en tabla PAYMENTS asociados a esa venta
                Payment::where('sale_id', $venta->id)
                    ->where('deleted', 0)
                    ->update(['deleted' => 1]);

                // 4. Obtener productos y restaurar stock
                $detalles = SaleDetail::where('sale_id', $venta->id)->get();

                foreach ($detalles as $detalle) {
                    $this->restaurarStockProducto(
                        $detalle->product_id,
                        $detalle->quantity
                    );
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'Venta anulada, stock restaurado y pagos desactivados correctamente.'
            ]);
        } catch (\Throwable $e) {
            Log::error("❌ Error al anular venta: " . $e->getMessage());

            return response()->json([
                'status' => false,
                'error' => 'Error inesperado al anular la venta: ' . $e->getMessage()
            ]);
        }
    }

    public function details(Request $request)
    {
        try {
            $sale_id = $request->sale_id;

            // Obtener la venta con todas sus relaciones
            $sale = Sale::with([
                'client',
                'details.product',
                'payments.payment_method'
            ])->findOrFail($sale_id);

            // Mapear los productos
            $productos = $sale->details->map(function ($detail) {
                return [
                    'id' => $detail->product_id,
                    'nombre' => $detail->product->nombre,
                    'precio' => round($detail->unit_price, 2),
                    'cantidad' => round($detail->quantity, 2),
                    'subtotal' => round($detail->subtotal, 2),
                ];
            });

            // Mapear los pagos
            $pagos = $sale->payments->map(function ($payment) {
                return [
                    'metodo_pago' => $payment->payment_method->nombre ?? 'N/A',
                    'monto' => round($payment->subtotal, 2),
                    'fecha' => $payment->created_at->format('d/m/Y H:i'),
                ];
            });

            // Información de la venta
            $ventaInfo = [
                'id' => $sale->id,
                'fecha' => $sale->date->format('d/m/Y H:i:s'),
                'cliente' => $sale->client->business_name ?? $sale->client_name ?? 'Varios',
                'fecha_entrega' => $sale->delivery_date,
                'hora_entrega' => $sale->delivery_hour,
                'direccion' => $sale->address,
                'referencia' => $sale->reference,
                'observacion' => $sale->observation,
                'total' => round($sale->total, 2),
                'saldo' => round($sale->saldo(), 2),
                'telefono' => $sale->phone,
                'voucher_type' => $sale->voucher_type,
                'number' => $sale->number,
            ];

            // Retorna los detalles en formato JSON
            return response()->json([
                'status' => true,
                'productos' => $productos,
                'pagos' => $pagos,
                'venta' => $ventaInfo,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al obtener detalles de venta: ' . $e->getMessage(),
            ], 500);
        }
    }


    private function restaurarStockProducto($productId, $cantidadRestaurar)
    {
        $product = Product::find($productId);
        $product->quantity += $cantidadRestaurar;
        $product->save();
    }

    public function getInvoiceById($id)
    {
        $url = config('apisunat.url') . '/documents/' . $id . '/getById';

        Log::error('url: ' . $url);

        $response = Http::get($url);
        $data = $response->object();
        if ($response->failed()) {
            return response()->json(['status' => false, 'error' => $data->error->message]);
        }

        return response()->json(['status' => true, 'data' => $response->json()]);
    }



    public function sale_print(Request $request)
    {
        try {
            $sale_id = $request->sale_id;
            // Cargar venta con relaciones correctas
            $sale = Sale::with([
                'client',
                'user',
                'location',
                'details.product',
                'payments.paymentMethod',
            ])->findOrFail($sale_id);

            // Productos
            $productos = $sale->details->map(function ($detail) {
                return [
                    'nombre' => $detail->product->name ?? 'Producto',
                    'precio' => round($detail->unit_price, 2),
                    'cantidad' => round($detail->quantity, 2),
                    'subtotal' => round($detail->subtotal, 2),
                ];
            });

            // Pagos
            $pagos = $sale->payments->map(function ($payment) {
                return [
                    'metodo_pago' => $payment->paymentMethod->name ?? 'N/A',
                    'monto' => round($payment->subtotal, 2),
                    'fecha' => $payment->created_at ? $payment->created_at->format('d/m/Y H:i') : '',
                ];
            });

            $type_sale = $sale->type_sale;
            $tipo = $type_sale == 0 ? 'Venta directa' : ($type_sale == 1 ? 'Venta anticipada' : (($type_sale == 2 || $type_sale == 3) ? 'Venta delivery' : 'Otro'));

            // Info venta
            $ventaInfo = [
                'id' => $sale->id,
                'cliente' => $sale->client->nombre ?? $sale->client_name ?? 'Varios',
                'document' => $sale->client->document ?? '00000000',
                'tipo' => $tipo,
                'type_sale' => $sale->type_sale,
                'fecha' => $sale->date ? \Carbon\Carbon::parse($sale->date)->format('d/m/Y H:i:s') : '',
                'referencia' => $sale->reference ?? $sale->referencia ?? '',
                'observacion' => $sale->observation ?? $sale->observacion ?? '',
                'total' => round($sale->total, 2),
                'saldo' => method_exists($sale, 'saldo') ? round($sale->saldo(), 2) : 0,
                'user_id' => $sale->usuario->email ?? $sale->usuario->name ?? 'No especificado',
                'voucher_type' => $sale->voucher_type,
                'number' => $sale->number,
                'ticket_number' => $sale->ticket_number ?? '',
                'location_name' => $sale->location->name ?? 'Sin sede',
            ];

            return response()->json([
                'status' => true,
                'productos' => $productos,
                'pagos' => $pagos,
                'venta' => $ventaInfo,
                'now' => now()->format('d/m/Y H:i:s'),
                'user' => ['name' => Auth::user()->email ?? 'Usuario'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al obtener datos para impresión: ' . $e->getMessage(),
            ], 500);
        }
    }
}

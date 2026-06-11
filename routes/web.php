<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CashCloseController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


require __DIR__ . '/auth.php';

Route::get('/storage', function () {
    Artisan::call('storage:link');
});

Route::get('/', function () {
    return view('auth.login');
})->middleware('guest');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', function () {
        return view('errors.maintenance'); //cambiar a ruta de dashboard cuando se desarrolle
    })->name('dashboard');

    Route::post('/user/set-turno', [UserController::class, 'setTurno'])->name('user.setTurno');

    //Almacen para administrar productos y stock
    Route::post('/update-location', [StorageController::class, 'updateLocation'])->name('updateLocation');

    //Productos
    Route::get('/api/search-products-r/', [ProductController::class, 'searchrs'])->name('products.searchrs');
    Route::get('/api/search-products-v/', [ProductController::class, 'searchpv'])->name('products.searchpv');
    Route::get('/api/search-products/', [ProductController::class, 'buscar'])->name('products.buscar');

    Route::resource('products', ProductController::class);

    Route::post('/setLocation', [UserController::class, 'setLocation'])->name('user.setLocation');

    Route::resource('sizes', SizeController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('tables', TableController::class);
    Route::resource('payment_methods', PaymentMethodController::class);

    Route::get('/api/search-client/', [ClientController::class, 'search'])->name('clients.search');
    Route::resource('clients', ClientController::class);


    Route::resource('employees', EmployeeController::class);
    Route::resource('roles', RolController::class);
    Route::resource('users', UserController::class);
    Route::resource('storages', StorageController::class);
    Route::resource('cashClose', CashCloseController::class);


    Route::post('/api/save-supplier/', [SupplierController::class, 'save_ajax'])->name('suppliers.saveSupplier');
     Route::get('/api/search-supplier/', [SupplierController::class, 'search'])->name('suppliers.search');


    Route::get('/purchases/pdf/product', [PurchaseController::class, 'generatePDFProduct'])->name('purchases.pdfProduct');
    Route::get('/purchases/pdf/allproducts', [PurchaseController::class, 'generatePDFAllProducts'])->name('purchases.pdfAllProducts');
    Route::get('/purchases/pdf_report', [PurchaseController::class, 'pdf'])->name('purchases.pdf');
    Route::get('/purchases/pdf_report_general', [PurchaseController::class, 'pdf_general'])->name('purchases.pdfGeneral');
    Route::get('purchases/excel', [PurchaseController::class, 'excel'])->name('purchases.excel');
    Route::resource('purchases', PurchaseController::class);
    Route::get('/buscar-supplier', [PurchaseController::class, 'buscarSuppliers'])->name('buscar.suppliers');
    Route::get('/buscar-product', [PurchaseController::class, 'buscarProducts'])->name('buscar.products');

    Route::post('/mesas/abrir/{id}', [SaleController::class, 'abrirMesa'])->name('mesas.abrir');
    Route::get('/mesas/pedido/{id}', [SaleController::class, 'verPedido'])->name('mesas.pedido');
    Route::post('/mesas/{id}/cerrar', [SaleController::class, 'cerrarMesa'])->name('mesas.cerrar');


    Route::post('sale_print',[SaleController::class, 'sale_print'])->name('sale_print');


    Route::post('/orders/{orderId}/addproducts', [SaleController::class, 'addProductToOrder'])->name('orders.addProduct');
    Route::post('/orders/confirm', [SaleController::class, 'confirmarPedido'])->name('orders.confirm');
    Route::post('/orders/preaccount', [SaleController::class, 'precuenta'])->name('orders.preaccount');


    Route::get('/payments/listar', [PaymentController::class, 'listar'])->name('payment.listar');


    //Ventas
    Route::get('/sunat/consultar', [SaleController::class, 'consultarSunat']);
    Route::get('restaurante', [SaleController::class, 'restaurante'])->name('sales.restaurante');
    Route::get('/api/products-by-category/{categoryId}', [SaleController::class, 'getProductsByCategory'])->name('sales.getProductsByCategory');
    Route::get('/api/all-products', [SaleController::class, 'getAllProducts'])->name('sales.getAllProducts');
    Route::get('sales/details', [SaleController::class, 'details'])->name('sales.details');
    Route::get('/sales/{id}/pdf-a4', [SaleController::class, 'pdfA4'])->name('sales.pdfA4');
    Route::get('/ventas/anular', [SaleController::class, 'anular'])->name('sales.anular');
    Route::get('sales/getVoucherData',[SaleController::class, 'getVoucherData'])->name('sales.getVoucherData');
    Route::get('/sales/historic', [SaleController::class, 'historic'])->name('sales.historic');
    Route::resource('sales', SaleController::class);
});

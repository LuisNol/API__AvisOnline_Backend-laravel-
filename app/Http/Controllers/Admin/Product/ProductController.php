<?php

namespace App\Http\Controllers\Admin\Product;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Product\Product;
use App\Models\Product\Categorie;
use App\Http\Controllers\Controller;
use App\Models\Product\ProductImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Product\ProductCollection;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $categorie_id = $request->categorie_id;
        
        $user = auth('api')->user();
        $query = Product::filterAdvanceProduct($search, $categorie_id);
        
        // Si el usuario tiene rol Admin o permiso manage-all-announcements, puede ver todos los productos
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-all-announcements')) {
            // Si solo tiene permiso para gestionar sus propios productos
            if ($user->hasPermission('manage-own-announcements')) {
                $query = $query->where('user_id', $user->id);
            }
        }
        
        $products = $query->orderBy("id", "desc")->paginate(25);

        return response()->json([
            "total" => $products->total(),
            "products" => ProductCollection::make($products),
        ]);
    }

    public function config(){
        // Solo categorías de primer nivel para anuncios clasificados
        $categories = Categorie::where("state",1)->get();
        
        return response()->json([
            "categories" => $categories,
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // DEBUGGING: Examinar la solicitud completa para entender el problema del upload
        \Log::info('=== DEBUGGING UPLOAD DE IMAGEN ===', [
            'request_all' => $request->all(),
            'request_files' => $request->allFiles(),
            'has_portada' => $request->hasFile("portada"),
            'file_portada' => $request->file("portada"),
            'content_type' => $request->header('Content-Type'),
            'content_length' => $request->header('Content-Length'),
            'request_method' => $request->getMethod(),
            'all_file_keys' => array_keys($request->allFiles()),
            'php_max_filesize' => ini_get('upload_max_filesize'),
            'php_post_max_size' => ini_get('post_max_size'),
            'php_max_file_uploads' => ini_get('max_file_uploads')
        ]);

        // Registrar la información de la solicitud
        \Log::info('Solicitud para crear producto recibida', [
            'headers' => $request->header(),
            'user_id' => auth('api')->id(),
            'permission_header' => $request->header('X-User-Permission')
        ]);
        
        // Verificar si el usuario tiene permisos para crear productos
        $user = auth('api')->user();
        
        // Obtener el permiso específico del encabezado si existe
        $headerPermission = $request->header('X-User-Permission');
        \Log::info('Permiso en el encabezado: ' . $headerPermission);
        
        // Verificar permisos basado en el encabezado y los permisos del usuario
        $isAdmin = $user->hasRole('Admin');
        $canManageProducts = $user->hasPermission('manage-all-announcements');
        $canManageOwnProducts = $user->hasPermission('manage-own-announcements');
        
        \Log::info('Verificación de permisos', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'is_admin' => $isAdmin,
            'has_manage_products' => $canManageProducts,
            'has_manage_own_products' => $canManageOwnProducts,
            'header_permission' => $headerPermission
        ]);
        
        // Si el usuario no tiene ningún permiso para crear productos
        if (!$isAdmin && !$canManageProducts && !$canManageOwnProducts) {
            \Log::warning('Intento de crear producto sin permisos', [
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);
            
            return response()->json([
                "message" => 403, 
                "message_text" => "No tienes permiso para crear productos"
            ]);
        }
        
        // VALIDAR LÍMITE DE 5 ANUNCIOS POR USUARIO (solo para usuarios normales)
        if (!$isAdmin && !$canManageProducts) {
            $userProductsCount = Product::where('user_id', $user->id)->count();
            if ($userProductsCount >= 5) {
                \Log::warning('Intento de crear más de 5 anuncios', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'current_products' => $userProductsCount
                ]);
                
                return response()->json([
                    "message" => 403, 
                    "message_text" => "Has alcanzado el límite máximo de 5 anuncios por usuario. Elimina algún anuncio existente para crear uno nuevo."
                ]);
            }
        }
        
        // Verificar si el producto ya existe
        $isValid = Product::where("title", $request->title)->first();
        if ($isValid) {
            return response()->json([
                "message" => 403, 
                "message_text" => "El nombre del producto ya existe"
            ]);
        }
        
        // DEBUGGING: Examinar la solicitud completa
        \Log::info('Debugging upload de imagen', [
            'request_all' => $request->all(),
            'request_files' => $request->allFiles(),
            'has_portada' => $request->hasFile("portada"),
            'file_portada' => $request->file("portada"),
            'input_portada' => $request->input("portada"),
            'content_type' => $request->header('Content-Type'),
            'request_method' => $request->getMethod(),
            'all_file_keys' => array_keys($request->allFiles())
        ]);

        // Procesar la imagen si se proporcionó
        if ($request->hasFile("portada")) {
            $file = $request->file("portada");
            
            \Log::info('Archivo recibido', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_valid' => $file->isValid()
            ]);
            
            $filename = uniqid() . '_' . $file->getClientOriginalName();
            $publicPath = public_path('storage/products');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0775, true);
            }
            $file->move($publicPath, $filename);
            $request->request->add(["imagen" => 'products/' . $filename]);
        } else {
            \Log::warning('No se proporcionó imagen para el producto', [
                'all_files' => $request->allFiles(),
                'all_inputs' => array_keys($request->all()),
                'file_keys' => array_keys($request->allFiles()),
                'max_file_uploads' => ini_get('max_file_uploads'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size')
            ]);
            
            // Buscar archivos con nombres alternativos
            $possibleNames = ['portada', 'imagen', 'file', 'image', 'foto'];
            $foundFile = null;
            
            foreach ($possibleNames as $name) {
                if ($request->hasFile($name)) {
                    \Log::info("Archivo encontrado con nombre alternativo: {$name}");
                    $foundFile = $request->file($name);
                    break;
                }
            }
            
            if ($foundFile) {
                $filename = uniqid() . '_' . $foundFile->getClientOriginalName();
                $publicPath = public_path('storage/products');
                if (!file_exists($publicPath)) {
                    mkdir($publicPath, 0775, true);
                }
                $foundFile->move($publicPath, $filename);
                $request->request->add(["imagen" => 'products/' . $filename]);
            } else {
                $fileKeys = array_keys($request->allFiles());
                return response()->json([
                    "message" => 400, 
                    "message_text" => "Debe proporcionar una imagen para el producto. Archivos detectados: " . (empty($fileKeys) ? 'ninguno' : implode(', ', $fileKeys))
                ]);
            }
        }

        // Preparar datos adicionales
        $request->request->add(["slug" => Str::slug($request->title)]);
        $request->request->add(["tags" => $request->multiselect]);
        
        // Asignar el usuario actual como creador del producto
        $request->request->add(["user_id" => $user->id]);
        
        // Crear el producto
        try {
            \Log::info('Intentando crear producto', [
                'title' => $request->title,
                'user_id' => $user->id,
                'permission_used' => $headerPermission ?: ($canManageProducts ? 'manage-all-announcements' : 'manage-own-announcements')
            ]);
            
            $product = Product::create($request->all());
            
            \Log::info('Producto creado exitosamente', [
                'product_id' => $product->id,
                'title' => $product->title,
                'user_id' => $user->id
            ]);
            
            return response()->json([
                "message" => 200,
                "product_id" => $product->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al crear producto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                "message" => 500,
                "message_text" => "Error al crear el producto: " . $e->getMessage()
            ]);
        }
    }

    public function imagens(Request $request){
        $product_id = $request->product_id;
        $product = Product::findOrFail($product_id);
        
        // Verificar si el usuario tiene permiso para modificar este producto
        $user = auth('api')->user();
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-all-announcements')) {
            if ($user->hasPermission('manage-own-announcements') && $product->user_id != $user->id) {
                return response()->json(["message" => 403, "message_text" => "No tienes permiso para modificar este producto"]);
            }
        }

        if($request->hasFile("imagen_add")){
            $path = Storage::putFile("products",$request->file("imagen_add"));
        }

        $product_imagen = ProductImage::create([
            "imagen" => $path,
            "product_id" => $product_id,
        ]);

        return response()->json([
            "imagen" => [
                "id" => $product_imagen->id,
                "imagen" => env("APP_URL")."storage/".$product_imagen->imagen,
            ]
        ]);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);
        
        // Verificar si el usuario tiene permiso para ver este producto
        $user = auth('api')->user();
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-all-announcements')) {
            if ($user->hasPermission('manage-own-announcements') && $product->user_id != $user->id) {
                return response()->json(["message" => 403, "message_text" => "No tienes permiso para ver este producto"]);
            }
        }

        return response()->json(["product" => ProductResource::make($product)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        \Log::info('🔄 Iniciando actualización de producto', [
            'product_id' => $id,
            'user_id' => auth('api')->user()->id,
            'title' => $request->title,
            'state_received' => $request->state,
            'state_type' => gettype($request->state),
            'all_data' => $request->except(['portada', '_method'])
        ]);

        $isValid = Product::where("id", "<>", $id)->where("title", $request->title)->first();
        if ($isValid) {
            \Log::warning('❌ Producto con título duplicado', ['title' => $request->title]);
            return response()->json(["message" => 403, "message_text" => "El nombre del producto ya existe"]);
        }
        
        $product = Product::findOrFail($id);
        
        \Log::info('📋 Producto actual antes de actualizar', [
            'current_state' => $product->state,
            'current_title' => $product->title
        ]);
        
        // Verificar si el usuario tiene permiso para editar este producto
        $user = auth('api')->user();
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-all-announcements')) {
            if ($user->hasPermission('manage-own-announcements') && $product->user_id != $user->id) {
                \Log::warning('❌ Usuario sin permisos para editar producto', [
                    'user_id' => $user->id,
                    'product_owner' => $product->user_id
                ]);
                return response()->json(["message" => 403, "message_text" => "No tienes permiso para editar este producto"]);
            }
        }
        
        if ($request->hasFile("portada")) {
            if ($product->imagen) {
                Storage::delete($product->imagen);
            }
            $path = Storage::putFile("products", $request->file("portada"));
            $request->request->add(["imagen" => $path]);
            \Log::info('📷 Imagen actualizada', ['new_path' => $path]);
        }

        $request->request->add(["slug" => Str::slug($request->title)]);
        $request->request->add(["tags" => $request->multiselect]);
        
        \Log::info('📤 Datos finales para actualizar', [
            'state_to_update' => $request->state,
            'title' => $request->title,
            'location' => $request->location,
            'contact_phone' => $request->contact_phone,
            'updated_fields' => $request->except(['portada', '_method'])
        ]);
        
        $product->update($request->all());
        
        \Log::info('✅ Producto actualizado exitosamente', [
            'product_id' => $product->id,
            'new_state' => $product->fresh()->state,
            'new_title' => $product->fresh()->title
        ]);
        
        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        
        // Verificar si el usuario tiene permiso para eliminar este producto
        $user = auth('api')->user();
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-all-announcements')) {
            if ($user->hasPermission('manage-own-announcements') && $product->user_id != $user->id) {
                return response()->json(["message" => 403, "message_text" => "No tienes permiso para eliminar este producto"]);
            }
        }
        
        // Eliminar imagen principal si existe
        if($product->imagen){
            Storage::delete($product->imagen);
        }
        
        // Eliminar todas las imágenes adicionales
        foreach($product->images as $image){
            if($image->imagen){
                Storage::delete($image->imagen);
            }
            $image->forceDelete();
        }
        
        // Eliminación física permanente
        $product->forceDelete();
        
        return response()->json([
            "message" => 200
        ]);
    }

    public function delete_imagen(string $id)
    {
        $productImage = ProductImage::findOrFail($id);
        $product = Product::findOrFail($productImage->product_id);
        
        // Verificar si el usuario tiene permiso para eliminar esta imagen
        $user = auth('api')->user();
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-all-announcements')) {
            if ($user->hasPermission('manage-own-announcements') && $product->user_id != $user->id) {
                return response()->json(["message" => 403, "message_text" => "No tienes permiso para eliminar esta imagen"]);
            }
        }
        
        if($productImage->imagen){
            Storage::delete($productImage->imagen);
        }
        $productImage->forceDelete();
        return response()->json([
            "message" => 200
        ]);
    }

    /**
     * Obtener estadísticas del usuario actual
     * Devuelve el conteo de anuncios y otros datos relevantes
     */
    public function getUserStats()
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        // Obtener solo anuncios del usuario actual
        $userProducts = Product::where('user_id', $user->id);
        
        $totalCount = $userProducts->count();
        $maxAllowed = 5; // Límite fijo de 5 anuncios
        
        $stats = [
            'total_announcements' => $totalCount,
            'active_announcements' => $userProducts->where('state', 1)->count(),
            'inactive_announcements' => $userProducts->where('state', 0)->count(),
            'pending_announcements' => $userProducts->where('state', 2)->count(),
            'max_allowed' => $maxAllowed,
            'remaining_slots' => max(0, $maxAllowed - $totalCount),
            'total_views' => $userProducts->sum('views_count'),
            'expired_count' => $userProducts->where('expires_at', '<', now())->count(),
            'expiring_soon' => $userProducts->whereBetween('expires_at', [now(), now()->addDays(7)])->count()
        ];

        \Log::info('Estadísticas del usuario calculadas:', [
            'user_id' => $user->id,
            'total_count' => $totalCount,
            'max_allowed' => $maxAllowed,
            'remaining_calculated' => $maxAllowed - $totalCount,
            'stats' => $stats
        ]);

        return response()->json([
            'message' => 200,
            'stats' => $stats
        ]);
    }
}


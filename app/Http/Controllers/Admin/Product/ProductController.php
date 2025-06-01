<?php

namespace App\Http\Controllers\Admin\Product;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Product\Brand;
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
        $categorie_first_id = $request->categorie_first_id;
        $categorie_second_id = $request->categorie_second_id;
        $categorie_third_id = $request->categorie_third_id;
        $brand_id = $request->brand_id;
        
        $user = auth('api')->user();
        $query = Product::filterAdvanceProduct($search, $categorie_first_id, $categorie_second_id, $categorie_third_id, $brand_id);
        
        // Si el usuario tiene rol Admin o permiso manage-products, puede ver todos los productos
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-products')) {
            // Si solo tiene permiso para gestionar sus propios productos
            if ($user->hasPermission('manage-own-products')) {
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
        $categories_first = Categorie::where("state",1)->where("categorie_second_id",NULL)->where("categorie_third_id",NULL)->get();
        $categories_seconds = Categorie::where("state",1)->where("categorie_second_id","<>",NULL)->where("categorie_third_id",NULL)->get();
        $categories_thirds = Categorie::where("state",1)->where("categorie_second_id","<>",NULL)->where("categorie_third_id","<>",NULL)->get();

        $brands = Brand::where("state",1)->get();
        return response()->json([
            "categories_first" => $categories_first,
            "categories_seconds" => $categories_seconds,
            "categories_thirds" => $categories_thirds,
            "brands" => $brands,
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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
        $canManageProducts = $user->hasPermission('manage-products');
        $canManageOwnProducts = $user->hasPermission('manage-own-products');
        
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
        
        // Verificar si el producto ya existe
        $isValid = Product::where("title", $request->title)->first();
        if ($isValid) {
            return response()->json([
                "message" => 403, 
                "message_text" => "El nombre del producto ya existe"
            ]);
        }
        
        // Procesar la imagen si se proporcionó
        if ($request->hasFile("portada")) {
            $file = $request->file("portada");
            $filename = uniqid() . '_' . $file->getClientOriginalName();
            $publicPath = public_path('storage/products');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0775, true);
            }
            $file->move($publicPath, $filename);
            $request->request->add(["imagen" => 'products/' . $filename]);
        } else {
            \Log::warning('No se proporcionó imagen para el producto');
            return response()->json([
                "message" => 400, 
                "message_text" => "Debe proporcionar una imagen para el producto"
            ]);
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
                'permission_used' => $headerPermission ?: ($canManageProducts ? 'manage-products' : 'manage-own-products')
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
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-products')) {
            if ($user->hasPermission('manage-own-products') && $product->user_id != $user->id) {
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
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-products')) {
            if ($user->hasPermission('manage-own-products') && $product->user_id != $user->id) {
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
        $isValid = Product::where("id", "<>", $id)->where("title", $request->title)->first();
        if ($isValid) {
            return response()->json(["message" => 403, "message_text" => "El nombre del producto ya existe"]);
        }
        
        $product = Product::findOrFail($id);
        
        // Verificar si el usuario tiene permiso para editar este producto
        $user = auth('api')->user();
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-products')) {
            if ($user->hasPermission('manage-own-products') && $product->user_id != $user->id) {
                return response()->json(["message" => 403, "message_text" => "No tienes permiso para editar este producto"]);
            }
        }
        
        if ($request->hasFile("portada")) {
            if ($product->imagen) {
                Storage::delete($product->imagen);
            }
            $path = Storage::putFile("products", $request->file("portada"));
            $request->request->add(["imagen" => $path]);
        }

        $request->request->add(["slug" => Str::slug($request->title)]);
        $request->request->add(["tags" => $request->multiselect]);
        $product->update($request->all());
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
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-products')) {
            if ($user->hasPermission('manage-own-products') && $product->user_id != $user->id) {
                return response()->json(["message" => 403, "message_text" => "No tienes permiso para eliminar este producto"]);
            }
        }
        
        $product->delete();
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
        if (!$user->hasRole('Admin') && !$user->hasPermission('manage-products')) {
            if ($user->hasPermission('manage-own-products') && $product->user_id != $user->id) {
                return response()->json(["message" => 403, "message_text" => "No tienes permiso para eliminar esta imagen"]);
            }
        }
        
        if($productImage->imagen){
            Storage::delete($productImage->imagen);
        }
        $productImage->delete();
        return response()->json([
            "message" => 200
        ]);
    }
}

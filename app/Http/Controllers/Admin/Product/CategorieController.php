<?php

namespace App\Http\Controllers\Admin\Product;

use Illuminate\Http\Request;
use App\Models\Product\Categorie;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Product\CategorieResource;
use App\Http\Resources\Product\CategorieCollection;

class CategorieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $categories = Categorie::where("name","like","%".$search."%")->orderBy("position","asc")->paginate(25);

        return response()->json([
            "total" => $categories->total(),
            "categories" => CategorieCollection::make($categories),
        ]);
    }

    public function config(){
        // Solo categorías de primer nivel para anuncios clasificados
        $categories = Categorie::where("state", 1)->orderBy("position", "asc")->get();

        return response()->json([
            "categories" => $categories,
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $is_exists = Categorie::where("name",$request->name)->first();
        if($is_exists){
            return response()->json(["message" => 403, "message_text" => "Ya existe una categoría con este nombre"]);
        }
        
        // Manejo de imagen
        $imagen = null;
        if($request->hasFile("image")){
            $path = Storage::putFile("categories",$request->file("image"));
            $imagen = $path;
        }
        
        // Manejar posiciones automáticamente
        $requested_position = $request->position ?? null;
        
        if ($requested_position) {
            // Si especifica una posición, mover las demás hacia abajo
            Categorie::where('position', '>=', $requested_position)
                    ->increment('position');
            $final_position = $requested_position;
        } else {
            // Si no especifica, usar la siguiente disponible
            $max_position = Categorie::max('position') ?? 0;
            $final_position = $max_position + 1;
        }
        
        // Datos específicos para crear categoría
        $categorie = Categorie::create([
            'name' => $request->name,
            'icon' => $request->icon ?? '',
            'imagen' => $imagen,
            'position' => $final_position,
            'state' => 1,
        ]);
        
        return response()->json([
            "message" => 200,
            "categorie" => CategorieResource::make($categorie),
            "message_text" => "Categoría creada en posición " . $final_position
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $categorie = Categorie::findOrFail($id);

        return response()->json(["categorie" => CategorieResource::make($categorie)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $is_exists = Categorie::where("id",'<>',$id)->where("name",$request->name)->first();
        if($is_exists){
            return response()->json(["message" => 403, "message_text" => "Ya existe una categoría con este nombre"]);
        }
        
        $categorie = Categorie::findOrFail($id);
        $old_position = $categorie->position;
        $new_position = $request->position ?? $categorie->position;
        
        // Manejo de imagen
        $imagen = $categorie->imagen; // Mantener imagen actual por defecto
        if($request->hasFile("image")){
            if($categorie->imagen){
                Storage::delete($categorie->imagen);
            }
            $path = Storage::putFile("categories",$request->file("image"));
            $imagen = $path;
        }
        
        // Si la posición cambió, reordenar las demás categorías
        if ($old_position != $new_position) {
            if ($new_position < $old_position) {
                // Moviendo hacia arriba: incrementar posiciones entre new_position y old_position
                Categorie::where('id', '!=', $id)
                        ->where('position', '>=', $new_position)
                        ->where('position', '<', $old_position)
                        ->increment('position');
            } else {
                // Moviendo hacia abajo: decrementar posiciones entre old_position y new_position
                Categorie::where('id', '!=', $id)
                        ->where('position', '>', $old_position)
                        ->where('position', '<=', $new_position)
                        ->decrement('position');
            }
        }
        
        // Actualizar datos específicos
        $categorie->update([
            'name' => $request->name,
            'icon' => $request->icon ?? $categorie->icon,
            'imagen' => $imagen,
            'position' => $new_position,
            'state' => $request->state ?? $categorie->state,
        ]);
        
        return response()->json([
            "message" => 200,
            "categorie" => CategorieResource::make($categorie),
            "message_text" => "Categoría actualizada. Posición: " . $new_position
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $categorie = Categorie::findOrFail($id);
        
        // Verificar si tiene productos asociados
        if($categorie->products->count() > 0){
            return response()->json(["message" => 403,"message_text" => "No se puede eliminar la categoría porque tiene anuncios asociados"]);
        }

        // Eliminar imagen si existe
        if($categorie->imagen){
            Storage::delete($categorie->imagen);
        }
        
        // Reordenar posiciones de las categorías restantes
        Categorie::where('position', '>', $categorie->position)->decrement('position');
        
        // Eliminación física permanente
        $categorie->forceDelete();
        
        return response()->json(["message" => 200]);
    }
}

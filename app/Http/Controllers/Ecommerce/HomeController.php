<?php

namespace App\Http\Controllers\Ecommerce;

use Illuminate\Http\Request;
use App\Models\Product\Product;
use App\Models\Product\Categorie;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\CategorieResource;
use App\Http\Resources\Product\CategorieCollection;

class HomeController extends Controller
{
    /**
     * Datos principales para la página de inicio
     * GET /api/ecommerce/home
     */
    public function home()
    {
        // Categorías activas ordenadas por posición
        $categories = Categorie::where('state', 1)
            ->orderBy('position', 'asc')
            ->take(8)
            ->get();

        // Anuncios más recientes (activos)
        $recent_products = Product::where('state', 1)
            ->with(['categorie_first', 'images', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // Anuncios destacados (los que tienen más vistas)
        $featured_products = Product::where('state', 1)
            ->with(['categorie_first', 'images', 'user'])
            ->orderBy('views_count', 'desc')
            ->take(8)
            ->get();

        // Anuncios populares (los más vistos en los últimos 30 días)
        $popular_products = Product::where('state', 1)
            ->where('created_at', '>=', now()->subDays(30))
            ->with(['categorie_first', 'images', 'user'])
            ->orderBy('views_count', 'desc')
            ->take(8)
            ->get();

        // Estadísticas generales
        $stats = [
            'total_announcements' => Product::where('state', 1)->count(),
            'total_categories' => Categorie::where('state', 1)->count(),
            'total_users' => \App\Models\User::count(),
            'announcements_today' => Product::where('state', 1)
                ->whereDate('created_at', today())
                ->count()
        ];

        return response()->json([
            'categories' => CategorieCollection::make($categories),
            'recent_products' => ProductCollection::make($recent_products),
            'featured_products' => ProductCollection::make($featured_products),
            'popular_products' => ProductCollection::make($popular_products),
            'stats' => $stats
        ]);
    }

    /**
     * Categorías para menús de navegación
     * GET /api/ecommerce/menus
     */
    public function menus()
    {
        $categories = Categorie::where('state', 1)
            ->withCount('products')
            ->orderBy('position', 'asc')
            ->get();

        return response()->json([
            'categories' => CategorieCollection::make($categories)
        ]);
    }

    /**
     * Detalles de un anuncio específico
     * GET /api/ecommerce/product/{slug}
     */
    public function show_product($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('state', 1)
            ->with(['categorie_first', 'images', 'user'])
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Anuncio no encontrado'
            ], 404);
        }

        // Incrementar contador de vistas
        $product->increment('views_count');

        // Anuncios relacionados de la misma categoría
        $related_products = Product::where('state', 1)
            ->where('categorie_first_id', $product->categorie_first_id)
            ->where('id', '!=', $product->id)
            ->with(['categorie_first', 'images', 'user'])
            ->take(4)
            ->get();

        return response()->json([
            'product' => ProductResource::make($product),
            'related_products' => ProductCollection::make($related_products)
        ]);
    }

    /**
     * Configuración para filtros avanzados
     * GET /api/ecommerce/config-filter-advance
     */
    public function config_filter_advance()
    {
        // Categorías activas
        $categories = Categorie::where('state', 1)
            ->withCount('products')
            ->orderBy('position', 'asc')
            ->get();

        // Rango de precios
        $price_range = [
            'min_price' => Product::where('state', 1)->min('price_pen') ?? 0,
            'max_price' => Product::where('state', 1)->max('price_pen') ?? 10000
        ];

        // Ubicaciones más comunes
        $locations = Product::where('state', 1)
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->selectRaw('location, COUNT(*) as count')
            ->groupBy('location')
            ->orderBy('count', 'desc')
            ->take(10)
            ->pluck('location');

        return response()->json([
            'categories' => CategorieCollection::make($categories),
            'price_range' => $price_range,
            'locations' => $locations
        ]);
    }

    /**
     * Búsqueda y filtrado avanzado de anuncios
     * POST /api/ecommerce/filter-advance-product
     */
    public function filter_advance_product(Request $request)
    {
        $search = $request->search;
        $categorie_id = $request->categorie_id;
        $min_price = $request->min_price;
        $max_price = $request->max_price;
        $location = $request->location;
        $sort_by = $request->sort_by ?? 'recent'; // recent, price_asc, price_desc, popular

        $query = Product::where('state', 1)
            ->with(['categorie_first', 'images', 'user']);

        // Filtro por búsqueda de texto
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        // Filtro por categoría (soporte para múltiples categorías)
        if ($categorie_id) {
            if (is_array($categorie_id)) {
                // Si es un array, usar whereIn para múltiples categorías
                $query->whereIn('categorie_first_id', $categorie_id);
            } else {
                // Si es un solo valor, usar where normal
                $query->where('categorie_first_id', $categorie_id);
            }
        }

        // Filtro por rango de precios
        if ($min_price && $max_price) {
            $query->whereBetween('price_pen', [$min_price, $max_price]);
        } elseif ($min_price) {
            $query->where('price_pen', '>=', $min_price);
        } elseif ($max_price) {
            $query->where('price_pen', '<=', $max_price);
        }

        // Filtro por ubicación
        if ($location) {
            $query->where('location', 'like', "%{$location}%");
        }

        // Ordenamiento
        switch ($sort_by) {
            case 'price_asc':
                $query->orderBy('price_pen', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_pen', 'desc');
                break;
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'recent':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $products = $query->paginate(12);

        return response()->json([
            'total' => $products->total(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'products' => ProductCollection::make($products)
        ]);
    }

    /**
     * Anuncios promocionales o destacados
     * POST /api/ecommerce/campaing-discount-link
     */
    public function campaing_discount_link(Request $request)
    {
        // Anuncios con descuentos o promociones especiales
        $promotional_products = Product::where('state', 1)
            ->where(function($query) {
                // Productos con descuentos activos
                $query->whereHas('discount_products', function($q) {
                    $q->whereHas('discount', function($subq) {
                        $subq->where('state', 1)
                             ->where('start_date', '<=', now())
                             ->where('end_date', '>=', now());
                    });
                })
                // O productos de categorías con descuentos
                ->orWhereHas('categorie_first.discount_categories', function($q) {
                    $q->whereHas('discount', function($subq) {
                        $subq->where('state', 1)
                             ->where('start_date', '<=', now())
                             ->where('end_date', '>=', now());
                    });
                });
            })
            ->with(['categorie_first', 'images', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        return response()->json([
            'promotional_products' => ProductCollection::make($promotional_products)
        ]);
    }
} 
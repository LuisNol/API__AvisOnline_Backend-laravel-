<?php

namespace App\Helpers;

class UrlHelper
{
    /**
     * Genera una URL completa para archivos en storage
     *
     * @param string $path Ruta relativa al archivo en storage
     * @return string URL completa
     */
    public static function getStorageUrl($path)
    {
        if (empty($path)) {
            return null;
        }
        
        // Eliminar barras iniciales y finales para evitar problemas de doble barra
        $path = trim($path, '/');
        return asset('storage/' . $path);
    }
} 
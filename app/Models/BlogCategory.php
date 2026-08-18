<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (BlogCategory $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'category_id');
    }

    public function publishedPosts(): HasMany
    {
        return $this->posts()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    /**
     * Nom de la catégorie dans la langue courante.
     *
     * Le nom stocké en base est français, et il s'affichait tel quel dans les
     * cinq langues : les cinq déclinaisons d'une même catégorie portaient donc
     * un titre identique, ce que Bing signalait comme doublon — et un lecteur
     * allemand lisait « Réglementation ».
     *
     * Le repli sur la valeur en base est délibéré : une catégorie créée après
     * coup s'affichera sous son nom d'origine plutôt que sous une clé brute.
     */
    public function translatedName(): string
    {
        $cle = "app.blog_categories.{$this->slug}";

        return \Illuminate\Support\Facades\Lang::has($cle) ? __($cle) : $this->name;
    }
}

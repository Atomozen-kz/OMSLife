<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Orchid\Attachment\Attachable;
use Orchid\Attachment\Models\Attachment;
use Orchid\Filters\Filterable;
use Orchid\Platform\Concerns\Sortable;
use Orchid\Screen\AsSource;

class News extends Model
{
    use HasFactory, Sortable, AsSource,Filterable, Attachable;

    protected $table = 'news';
    protected $fillable = [
        'title',
        'mini_description',
        'photo',
        'full_text',
        'lang',
        'category_id',
        'status',
        'on_main',
        'sort'
    ];

    public function getSortColumnName(): string
    {
        return 'sort';
    }

    public function category()
    {
        return $this->belongsTo(NewsCategory::class);
    }

    protected static function booted()
    {
        static::deleting(function ($news) {
            // Получаем все медиафайлы, связанные с новостью
            $mediaFiles = $news->media;

            foreach ($mediaFiles as $media) {
                // Удаляем файл из файловой системы
                Storage::disk('public')->delete($media->file_path . $media->file_name);
            }

            // Удаление связанных записей медиафайлов (будет каскадным удалением)
            $news->media()->delete();
        });
    }

    public function media()
    {
        return $this->hasMany(NewsMedia::class);
    }

    public function comments()
    {
        return $this->hasMany(NewsComments::class);
    }

    public function likes()
    {
        return $this->hasMany(NewsLike::class);
    }
}

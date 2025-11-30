<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Screen\AsSource;

class OrganizationStructure extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;

    protected $table = 'organization_structure';

    protected $fillable = ['name_ru', 'name_kz', 'parent_id', 'is_promzona'];

    protected $allowedSorts = ['name_ru'];
    protected $allowedFilters = [
        'name_ru' =>Like::class
    ];

    // Метод для рекурсивного поиска самого старшего родителя
    public function getFirstParent()
    {
        if ($this->parent) {
            return $this->parent->getFirstParent();
        }

        return $this; // Если родителя нет, значит текущий элемент является корневым
    }

    public static function getFirstParentById($id)
    {
        $organization = self::find($id);
        if (!$organization) {
            return null;
        }
        while ($organization->parent) {
            $organization = $organization->parent;
        }
        return $organization;
    }


    public function getFirstParentAttribute()
    {
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
        }

        return $current; // Возвращает корневую структуру
    }

    public function getAllParentsAttribute()
    {
        $parents = [];
        $current = $this;

        while ($current->parent) {
            $parents[] = $current->parent; // Добавляем текущего родителя
            $current = $current->parent;  // Переходим к следующему уровню
        }

        return collect($parents); // Возвращаем коллекцию родителей
    }

    public function allChildrenCount(): int
    {
        return $this->children()->with('children')->get()->reduce(function ($count, $child) {
            return $count + 1 + $child->allChildrenCount();
        }, 0);
    }

    public function allRelatedOrganizationIds(): array
    {
        $childrenIds = $this->children()->with('children')->get()->flatMap(function ($child) {
            return $child->allRelatedOrganizationIds();
        })->toArray();

        return array_merge([$this->id], $childrenIds);
    }

    public function allSotrudnikCount(): int
    {
        $organizationIds = $this->allRelatedOrganizationIds();
        return Sotrudniki::whereIn('organization_id', $organizationIds)->count();
    }

    public function preloadedChildrenCount(array $preloadedChildren): int
    {
        return isset($preloadedChildren[$this->id]) ? count($preloadedChildren[$this->id]) : 0;
    }

    public function preloadedSotrudnikCount(array $preloadedSotrudniks): int
    {
        return $preloadedSotrudniks[$this->id] ?? 0;
    }


    /**
     * Связь с опросами.
     */
    public function surveys()
    {
        return $this->belongsToMany(Survey::class, 'organization_survey' ,'organization_id', 'survey_id');
    }

    public function parent()
    {
        return $this->belongsTo(OrganizationStructure::class, 'parent_id');
    }

    public function scopeFirstParent($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithFirstParent($query)
    {
        $table = with(new static)->getTable();

        return $query->select("{$table}.*")
            ->selectRaw("CONCAT({$table}.name_ru, IF(parent.name_ru IS NOT NULL, CONCAT(', ', parent.name_ru), '')) as full_name")
            ->leftJoin("{$table} as parent", "{$table}.parent_id", '=', 'parent.id');
    }

    public function getFullNameAttribute()
    {
        return $this->name_ru . ($this->parent ? ', ' . $this->parent->name_ru : '');
    }


    public function scopeFirstParentAndPromzona($query)
    {
        return $query->whereNull('parent_id')->where('is_promzona', true);
    }

    public function children()
    {
        return $this->hasMany(OrganizationStructure::class, 'parent_id');
    }

    public function promzonaObjects(){
        return $this->hasMany(PromzonaObject::class, 'id_organization');
    }

    public function getAllChildren()
    {
        $children = $this->children;

        foreach ($this->children as $child) {
            $children = $children->merge($child->getAllChildren());
        }

        return $children;
    }

    public function getAllParentIds()
    {
        $ids = [];
        $parent = $this->parent;

        while ($parent) {
            $ids[] = $parent->id;
            $parent = $parent->parent;
        }

        return $ids;
    }

    public function totalSotrudnikCount(): int
    {
        $allRelatedIds = $this->allRelatedOrganizationIds();
        return Sotrudniki::whereIn('organization_id', $allRelatedIds)->count();
    }

    public function totalRegisteredSotrudnikCount(): int
    {
        $allRelatedIds = $this->allRelatedOrganizationIds();
        return Sotrudniki::where('is_registered', true)->whereIn('organization_id', $allRelatedIds)->count();
    }
}

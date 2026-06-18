<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'label',
        'is_encrypted',
        'is_public',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function getValueAttribute(?string $value): ?string
    {
        if ($this->is_encrypted && $value) {
            try {
                return decrypt($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    public function setValueAttribute(?string $value): void
    {
        if ($this->is_encrypted && $value) {
            $this->attributes['value'] = encrypt($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settingsArray = Cache::remember('app_settings', 86400, function () {
            return self::all()->mapWithKeys(fn($item) => [
                $item->key => [
                    'value' => $item->getAttributes()['value'] ?? null,
                    'type' => $item->type,
                    'is_encrypted' => $item->is_encrypted,
                    'group' => $item->group,
                    'label' => $item->label,
                    'is_public' => $item->is_public,
                ]
            ])->toArray();
        });

        if (!isset($settingsArray[$key])) {
            return $default;
        }

        $settingData = $settingsArray[$key];
        $value = $settingData['value'];

        if ($settingData['is_encrypted'] && $value) {
            try {
                $value = decrypt($value);
            } catch (\Exception $e) {
                // fall back to raw value
            }
        }

        // Cast value based on type
        switch ($settingData['type']) {
            case 'integer':
                return (int) $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return is_array($value) ? $value : json_decode($value, true);
            default:
                return $value;
        }
    }

    public static function getGroup(string $group): Collection
    {
        $settingsArray = Cache::remember('app_settings', 86400, function () {
            return self::all()->mapWithKeys(fn($item) => [
                $item->key => [
                    'value' => $item->getAttributes()['value'] ?? null,
                    'type' => $item->type,
                    'is_encrypted' => $item->is_encrypted,
                    'group' => $item->group,
                    'label' => $item->label,
                    'is_public' => $item->is_public,
                ]
            ])->toArray();
        });

        $matching = collect();
        foreach ($settingsArray as $key => $data) {
            if ($data['group'] === $group) {
                $setting = new self();
                $setting->forceFill([
                    'key' => $key,
                    'value' => $data['value'],
                    'type' => $data['type'],
                    'group' => $data['group'],
                    'label' => $data['label'],
                    'is_encrypted' => $data['is_encrypted'],
                    'is_public' => $data['is_public'],
                ]);
                $setting->exists = true;
                $matching->push($setting);
            }
        }

        return $matching;
    }

    public static function set(string $key, mixed $value): bool
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        if ($setting->type === 'json' && !is_string($value)) {
            $value = json_encode($value);
        }

        if ($setting->type === 'boolean' && !is_string($value)) {
            $value = $value ? '1' : '0';
        }

        $setting->value = (string) $value;
        $saved = $setting->save();

        if ($saved) {
            self::flush();
        }

        return $saved;
    }

    public static function flush(): void
    {
        Cache::forget('app_settings');
    }
}

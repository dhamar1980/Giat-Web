<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class StockObat extends ObatBatch
{
    // Alias model for backward compatibility
    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder {
            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                if (is_array($column)) {
                    $remapped = [];
                    foreach ($column as $key => $val) {
                        $newKey = $key;
                        if ($key === 'id_apoteker') {
                            $newKey = 'id_apotek';
                        } elseif ($key === 'jumlah_stock') {
                            $newKey = 'stok_batch';
                        }
                        $remapped[$newKey] = $val;
                    }
                    return parent::where($remapped, $operator, $value, $boolean);
                }

                if ($column === 'id_apoteker') {
                    $column = 'id_apotek';
                } elseif ($column === 'jumlah_stock') {
                    $column = 'stok_batch';
                }
                return parent::where($column, $operator, $value, $boolean);
            }

            public function orderBy($column, $direction = 'asc')
            {
                if ($column === 'id_apoteker') {
                    $column = 'id_apotek';
                } elseif ($column === 'jumlah_stock') {
                    $column = 'stok_batch';
                }
                return parent::orderBy($column, $direction);
            }
        };
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        if ($column === 'jumlah_stock') {
            $column = 'stok_batch';
        }
        return parent::decrement($column, $amount, $extra);
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        if ($column === 'jumlah_stock') {
            $column = 'stok_batch';
        }
        return parent::increment($column, $amount, $extra);
    }
}

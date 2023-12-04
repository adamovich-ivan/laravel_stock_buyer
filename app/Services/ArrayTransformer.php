<?php

namespace App\Services;

class ArrayTransformer
{
    public static function transformToAssociative(array $data): array
    {
        // Убедимся, что существует хотя бы один подмассив для заголовков
        if (count($data) < 1) {
            return [];
        }

        // Первый подмассив используется как шаблон ключей
        $keys = array_map('trim', $data[0]);

        // Удаление первого элемента, который содержит заголовки столбцов
        array_shift($data);

        // Инициализация нового массива, который будет содержать ассоциативные массивы
        $associativeArray = [];

        foreach ($data as $row) {
            // Очистка значений от пробелов и специальных символов
            $row = array_map(function ($value) {
                // Удаление невидимых символов и лишних пробелов
                return trim($value, " \t\n\r\0\x0B\u{A0}");
            }, $row);

            // Если количество элементов в строке данных не соответствует количеству ключей,
            // пропускаем эту строку или обрабатываем ошибку соответствующим образом
            if (count($row) !== count($keys)) {
                continue; // или throw new \Exception('Row column count does not match keys count.');
            }

            // Объединение ключей и значений
            $associativeArray[] = array_combine($keys, $row);
        }

        return $associativeArray;
    }


    public static function transformArrayForBuying($originalArray): array
    {
        return array_map(function ($item) {
            return [
                'symbol' => $item['xtb api ticker'],
                'volume' => self::formatVolume($item['Purchase volume'])
            ];
        }, $originalArray);
    }

    private static function formatVolume($volume)
    {
        $value = str_replace(',', '.', $volume);
        return floatval($value);
    }

}


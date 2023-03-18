<?php

namespace App\Factory;

use App\Entity\Export;

final class ExportFactory
{
    public static function create(string $entity, int $userId, ?string $data): Export
    {
        $export = new Export();
        $export->setEntity($entity)
            ->setUserId($userId)
            ->setProgress(0)
            ->setStatus(Export::PENDING)
            ->setData($data);

        return $export;
    }
}

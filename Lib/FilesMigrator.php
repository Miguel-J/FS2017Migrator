<?php
/**
 * This file is part of FS2017Migrator plugin for FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\FS2017Migrator\Lib;

use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Core\Model\AttachedFileRelation;

/**
 * Description of FilesMigrator
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class FilesMigrator extends MigratorBase
{

    const TABLE_NAME = 'documentosfac';

    /**
     * 
     * @param int $offset
     *
     * @return bool
     */
    protected function migrationProcess(&$offset = 0): bool
    {
        if (false === $this->dataBase->tableExists(static::TABLE_NAME)) {
            return true;
        }

        $sql = "SELECT * FROM " . static::TABLE_NAME . " ORDER BY id ASC";
        foreach ($this->dataBase->selectLimit($sql, 50, $offset) as $row) {
            if ($this->fileExists($row['ruta'])) {
                $this->moveFile($row);
            }

            $offset++;
        }

        return true;
    }

    /**
     * 
     * @param string $ruta
     *
     * @return bool
     */
    private function fileExists(string $ruta): bool
    {
        $filePath = FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . 'FS2017Migrator' . DIRECTORY_SEPARATOR . $ruta;
        return false === empty($ruta) && \file_exists($filePath);
    }

    /**
     * 
     * @param array $row
     *
     * @return bool
     */
    private function moveFile($row): bool
    {
        $filePath = FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . 'FS2017Migrator' . DIRECTORY_SEPARATOR . $row['ruta'];
        $newPath = FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . $row['nombre'];
        if (false === \rename($filePath, $newPath)) {
            return false;
        }

        $newAttFile = new AttachedFile();
        $newAttFile->date = $row['fecha'];
        $newAttFile->path = $row['nombre'];
        if (false === $newAttFile->save()) {
            return false;
        }

        if ($row['idfactura']) {
            $this->newRelation($newAttFile->idfile, 'FacturaCliente', $row['idfactura'], $row['fecha']);
        }

        if ($row['idalbaran']) {
            $this->newRelation($newAttFile->idfile, 'AlbaranCliente', $row['idalbaran'], $row['fecha']);
        }

        if ($row['idpedido']) {
            $this->newRelation($newAttFile->idfile, 'PedidoCliente', $row['idpedido'], $row['fecha']);
        }

        if ($row['idpresupuesto']) {
            $this->newRelation($newAttFile->idfile, 'PresupuestoCliente', $row['idpresupuesto'], $row['fecha']);
        }

        if ($row['idfacturaprov']) {
            $this->newRelation($newAttFile->idfile, 'FacturaProveedor', $row['idfacturaprov'], $row['fecha']);
        }

        if ($row['idalbaranprov']) {
            $this->newRelation($newAttFile->idfile, 'AlbaranProveedor', $row['idalbaranprov'], $row['fecha']);
        }

        if ($row['idpedidoprov']) {
            $this->newRelation($newAttFile->idfile, 'PedidoProveedor', $row['idpedidoprov'], $row['fecha']);
        }

        return true;
    }

    /**
     * 
     * @param int    $idfile
     * @param string $model
     * @param int    $modelid
     * @param string $date
     *
     * @return bool
     */
    private function newRelation($idfile, $model, $modelid, $date): bool
    {
        $newRelation = new AttachedFileRelation();
        $newRelation->creationdate = $date;
        $newRelation->idfile = $idfile;
        $newRelation->model = $model;
        $newRelation->modelid = $modelid;
        return $newRelation->save();
    }
}

<?php
/**
 * This file is part of FS2017Migrator plugin for FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Dinamic\Model\PagoProveedor;
use FacturaScripts\Dinamic\Model\ReciboProveedor;

/**
 * Description of RecibosProveedorMigrator
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class RecibosProveedorMigrator extends InicioMigrator
{

    /**
     * 
     * @param int $offset
     *
     * @return bool
     */
    public function migrate(&$offset = 0)
    {
        return $this->migrateInTransaction($offset);
    }

    /**
     * 
     * @param ReciboCliente $receipt
     *
     * @return bool
     */
    protected function newPayment($receipt)
    {
        $sql = 'SELECT * FROM pagosdevolprov WHERE idrecibo = '
            . $this->dataBase->var2str($receipt->idrecibo) . ' ORDER BY idpagodevol ASC';
        foreach ($this->dataBase->select($sql) as $row) {
            $newPayment = new PagoProveedor($row);
            $newPayment->codpago = $receipt->codpago;
            $newPayment->importe = $row['tipo'] == 'Pago' ? $receipt->importe : 0 - $receipt->importe;
            if (!$newPayment->save()) {
                return false;
            }
        }

        return true;
    }

    /**
     * 
     * @param array $row
     *
     * @return bool
     */
    protected function newReceipt($row)
    {
        $newReceipt = new ReciboProveedor($row);
        $newReceipt->disablePaymentGeneration();
        $newReceipt->idempresa = AppSettings::get('default', 'idempresa');
        $newReceipt->fechapago = date('d-m-Y', strtotime($row['fechap']));
        $newReceipt->vencimiento = date('d-m-Y', strtotime($row['fechav']));
        $newReceipt->pagado = $row['estado'] === 'Pagado';
        if ($newReceipt->exists()) {
            return true;
        }

        return $newReceipt->save() ? $this->newPayment($newReceipt) : false;
    }

    /**
     * 
     * @param int $offset
     *
     * @return bool
     */
    protected function transactionProcess(&$offset = 0)
    {
        $sql = 'SELECT * FROM recibosprov ORDER BY idrecibo ASC';

        $rows = $this->dataBase->selectLimit($sql, 300, $offset);
        foreach ($rows as $row) {
            $done = $this->newReceipt($row);
            if (!$done) {
                return false;
            }

            $offset++;
        }

        return true;
    }
}
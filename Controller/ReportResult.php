<?php
/**
 * This file is part of Informes plugin for FacturaScripts
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Informes\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Plugins\Informes\Lib\Informes\AccountResultReport;
use FacturaScripts\Plugins\Informes\Lib\Informes\FamilyResultReport;
use FacturaScripts\Plugins\Informes\Lib\Informes\PurchasesResultReport;
use FacturaScripts\Plugins\Informes\Lib\Informes\SalesPurchasesResultReport;
use FacturaScripts\Plugins\Informes\Lib\Informes\SummaryResultReport;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class ReportResult extends Controller
{
    private $logLevels = ['critical', 'error', 'info', 'notice', 'warning'];

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["menu"] = "reports";
        $data["title"] = "result-report";
        $data["icon"] = "fas fa-poll";
        return $data;
    }

    public function getYears(): string
    {
        $html = '';
        $model = new Ejercicio();
        foreach ($model->all([], ['fechainicio' => 'desc'], 0, 0) as $row) {
            $emp = new Empresa();
            $emp->loadFromCode($row->idempresa);
            $html .= '<option value="' . $row->codejercicio . '">'
                . $row->nombre . ' | ' . $emp->nombrecorto
                . '</option>';
        }
        return $html;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->get('action', '');
        switch ($action) {
            case 'load-account':
                $this->loadAccount();
                break;

            case 'load-family-sales':
            case 'load-family-purchases':
                $this->loadFamily();
                break;

            case 'load-purchases':
                $this->loadPurchases();
                break;

            case 'load-sales':
            case 'load-purchases-product':
                $this->loadSalesPurchases($action);
                break;

            case 'load-summary':
                $this->loadSummary();
                break;
        }
    }

    protected function loadAccount(): void
    {
        $this->setTemplate(false);
        $content = [
            'codcuenta' => $this->request->request->get('parent_codcuenta'),
            'account' => AccountResultReport::render($this->request->request->all()),
            'messages' => Tools::log()->read('', $this->logLevels)
        ];
        $this->response->setContent(json_encode($content));
    }

    protected function loadFamily(): void
    {
        $this->setTemplate(false);
        $content = [
            'codfamilia' => $this->request->request->get('parent_codfamilia'),
            'family' => FamilyResultReport::render($this->request->request->all()),
            'messages' => Tools::log()->read('', $this->logLevels),
            'type' => $this->request->request->get('action')
        ];
        $this->response->setContent(json_encode($content));
    }

    protected function loadPurchases(): void
    {
        $this->setTemplate(false);
        $content = [
            'purchases' => PurchasesResultReport::render($this->request->request->all()),
            'messages' => Tools::log()->read('', $this->logLevels)
        ];
        $this->response->setContent(json_encode($content));
    }

    protected function loadSalesPurchases(string $action): void
    {
        $this->setTemplate(false);
        $key = ($action == "load-sales") ? "sales" : "purchasesProduct";

        $content = [
            $key => SalesPurchasesResultReport::render($this->request->request->all()),
            'messages' => Tools::log()->read('', $this->logLevels)
        ];
        $this->response->setContent(json_encode($content));
    }

    protected function loadSummary(): void
    {
        $this->setTemplate(false);
        $content = [
            'summary' => SummaryResultReport::render($this->request->request->all()),
            'charts' => SummaryResultReport::$charts,
            'messages' => Tools::log()->read('', $this->logLevels)
        ];
        $this->response->setContent(json_encode($content));
    }
}

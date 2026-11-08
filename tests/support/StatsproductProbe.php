<?php

class StatsproductExportInterrupt extends Exception
{
}

class StatsproductProbe extends statsproduct
{
    public $lastEngineParams;
    public $lastCsvExportParams;
    public $engineCallCount = 0;

    public function engine($params)
    {
        ++$this->engineCallCount;
        $this->lastEngineParams = $params;

        return 'graph-engine';
    }

    public function csvExport($datas)
    {
        $this->lastCsvExportParams = $datas;

        throw new StatsproductExportInterrupt('csvExport intercepted');
    }

    public function runGetData($layers)
    {
        $this->getData($layers);
    }

    public function exposeValues()
    {
        return $this->_values;
    }

    public function exposeLegend()
    {
        return $this->_legend;
    }

    public function resetGraphState()
    {
        $this->_values = array();
        $this->_legend = array();
        $this->_titles = array('main' => null, 'x' => null, 'y' => null);
    }

    public function setEmployeeStatsWindow($from, $to)
    {
        if (!is_object($this->_employee)) {
            $this->setEmployee(1);
        }
        $this->_employee->stats_date_from = $from;
        $this->_employee->stats_date_to = $to;
        Context::getContext()->employee->stats_date_from = $from;
        Context::getContext()->employee->stats_date_to = $to;
    }
}

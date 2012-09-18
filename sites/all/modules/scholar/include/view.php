<?php

/**
 * Klasa abstrakcyjna, kapsułkująca ustawienia widoku, tak, by
 * nie można ich było zmodyfikować podczas renderingu widoku.
 */
abstract class scholar_view_abstract
{
    private $_templateDir;
    private $_vars = array();

    abstract public function render($template);

    public function setTemplateDir($templateDir) // {{{
    {
        $templateDir = (string) $templateDir;

        if (empty($templateDir) || !is_dir($templateDir)) {
            throw new InvalidArgumentException('Invalid template directory: ' . $templateDir);
        }

        $this->_templateDir = $templateDir;
    } // }}}

    public function getTemplateDir() // {{{
    {
        return $this->_templateDir;
    } // }}}

    public function getTemplateFile($template) // {{{
    {
        $templateDir = $this->getTemplateDir();

        if ($templateDir) {
            $templateFile = $templateDir . '/' . ltrim($template, '/');
        } else {
            $templateFile = $template;
        }

        if (is_file($templateFile)) {
            return $templateFile;
        }

        return false;
    } // }}}

    public function assign($key, $value)
    {
        $this->_vars[$key] = $value;
        return $this;
    }

    public function assignFromArray($array)
    {
        foreach ($array as $key => $value) {
            $this->assign($key, $value);
        }
        return $this;
    }

    // wypisuje eskejpowana zawartosc zmiennej
    public function escape($value)
    {
        return str_replace(array('[', ']'), array('\[', '\]'), (string) $value);
    }

    // wypisuje zawartosc zmiennej eskejpowana do bycia atrybutem
    public function escapeAttr($value)
    {
        return str_replace('"', "''", (string) $value);
    }

    public function display($value)
    {
        echo $this->escape($value);
    }

    public function displayAttr($value)
    {
        echo $this->escapeAttr($value);
    }

    public function __get($key) // {{{
    {
        return isset($this->_vars[$key]) ? $this->_vars[$key] : null;
    } // }}}
}

class scholar_view extends scholar_view_abstract
{
    /**
     * @param string $template
     */
    public function render($template) // {{{
    {
        $templateFile = $this->getTemplateFile($template);

        if ($templateFile) {
            return $this->_render($templateFile);
        }

        throw new InvalidArgumentException('Unable to render template: ' . $template);
    } // }}}

    protected function _render() // {{{
    {
        ob_start();
        require func_get_arg(0);
        return ob_get_clean();
    } // }}}

    public function e($value) // {{{
    {
        return $this->escape($value);
    } // }}}
}

// vim: fdm=marker

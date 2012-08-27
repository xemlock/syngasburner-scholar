<?php

class scholar_view_vars implements Iterator
{
    private $_vars = array();
    private $_escape;

    public function __construct($escape = null, $vars = null) // {{{
    {
        if (null === $escape) {
            $this->_escape = 'htmlspecialchars';
        } else {
            if (!is_callable($escape)) {
                throw new InvalidArgumentException('Invalid escape function');
            }
            $this->_escape = $escape;
        }

        if (is_array($vars) || ($vars instanceof Iterator)) {
            foreach ($vars as $key => $value) {
                $this->assign($key, $value);
            }
        }
    } // }}}

    public function assign($key, $value) // {{{
    {
        if (is_array($value)) {
            $this->_vars[$key] = new self($this->_escape, $value);
        } else {
            $this->_vars[$key] = $value;
        }
        return $this;
    } // }}}

    public function get($key) // {{{
    {
        $value = $this->raw($key);
        return is_string($value) ? $this->escape($value) : $value;
    } // }}}

    public function raw($key) // {{{
    {
        return isset($this->_vars[$key]) ? $this->_vars[$key] : null;
    } // }}}

    /**
     * Sprawdza czy istnieje zmienna, jeżeli zmienna jest pustym
     * tekstem jest ona traktowana jakby jej nie było.
     *
     * @param string $key
     */
    public function has($key) // {{{
    {
        $value = $this->raw($key);

        if (is_string($value)) {
            return 0 < strlen($value);
        }

        return null !== $value;
    } // }}}

    public function escape($value) // {{{
    {
        return call_user_func($this->_escape, $value);
    } // }}}

    public function __get($key) // {{{
    {
        return $this->get($key);
    } // }}}

    public function current() // {{{
    {
        return current($this->_vars);
    } // }}}

    public function key() // {{{
    {
        return key($this->_vars);
    } // }}}

    public function next() // {{{
    {
        return next($this->_vars);
    } // }}}

    public function rewind() // {{{
    {
        reset($this->_vars);
    } // }}}

    public function valid() // {{{
    {
        return false !== $this->current();
    } // }}}

    public function __toString() // {{{
    {
        return '';
    } // }}}
}

/**
 * Klasa abstrakcyjna, kapsułkująca ustawienia widoku, tak, by
 * nie można ich było zmodyfikować podczas renderingu widoku.
 */
abstract class scholar_view_abstract extends scholar_view_vars
{
    private $_templateDir;

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

        drupal_set_message(t('Unable to render template: %template', array('%template' => $template)), 'error');
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

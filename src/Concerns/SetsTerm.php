<?php

namespace Lacasera\ElasticBridge\Concerns;

trait SetsTerm
{
    /**
     * @return $this
     */
    public function asBoolean()
    {
        $this->query->setTerm('bool');

        return $this;
    }

    /**
     * @return $this
     */
    public function asFuzzy()
    {
        $this->query->setTerm('fuzzy');

        return $this;
    }

    /**
     * @return $this
     */
    public function asIds()
    {
        $this->query->setTerm('ids');

        return $this;
    }

    /**
     * @return $this
     */
    public function asPrefix()
    {
        $this->query->setTerm('prefix');

        return $this;
    }

    /**
     * @return $this
     */
    public function asRange()
    {
        $this->query->setTerm('range');

        return $this;
    }

    /**
     * @return $this
     */
    public function asRegex()
    {
        $this->query->setTerm('regex');

        return $this;
    }

    /**
     * @return $this
     */
    public function asTerm()
    {
        $this->query->setTerm('term');

        return $this;
    }

    /**
     * @return $this
     */
    public function asTerms()
    {
        $this->query->setTerm('terms');

        return $this;
    }

    /**
     * @return $this
     */
    public function asTermSet()
    {
        $this->query->setTerm('terms_set');

        return $this;
    }

    /**
     * @return $this
     */
    public function asWildCard()
    {
        $this->query->setTerm('wildcard');

        return $this;
    }
}

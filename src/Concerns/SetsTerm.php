<?php

namespace Lacasera\ElasticBridge\Concerns;

use Lacasera\ElasticBridge\Builder\BridgeBuilder;

trait SetsTerm
{
    /**
     * @return BridgeBuilder
     */
    public function asBoolean(): BridgeBuilder
    {
        $this->query->setTerm('bool');

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function asFuzzy($options = []): BridgeBuilder
    {
        $this->query->setTerm('fuzzy');

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function asIds(): BridgeBuilder
    {
        $this->query->setTerm('ids');

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function asPrefix(): BridgeBuilder
    {
        $this->query->setTerm('prefix');

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function asRange(): BridgeBuilder
    {
        $this->query->setTerm('range');

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function asRegex(): BridgeBuilder
    {
        $this->query->setTerm('regex');

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function asTerm(): BridgeBuilder
    {
        $this->query->setTerm('term');

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function asTerms(): BridgeBuilder
    {
        $this->query->setTerm('terms');

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function asTermSet(): BridgeBuilder
    {
        $this->query->setTerm('terms_set');

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function asWildCard(): BridgeBuilder
    {
        $this->query->setTerm('wildcard');

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function asMatch(): BridgeBuilder
    {
        $this->query->setTerm('match');

        return $this;
    }

    /**
     * @return BridgeBuilder
     */
    public function asRaw(): BridgeBuilder
    {
        $this->query->setTerm('raw');

        return $this;
    }
}

<?php

namespace Lacasera\ElasticBridge\Concerns;

use Lacasera\ElasticBridge\Builder\BridgeBuilder;

trait SetsTerm
{
    public function asBoolean(): BridgeBuilder
    {
        $this->query->setTerm('bool');

        return $this;
    }

    public function asFuzzy(): BridgeBuilder
    {
        $this->query->setTerm('fuzzy');

        return $this;
    }

    public function asIds(): BridgeBuilder
    {
        $this->query->setTerm('ids');

        return $this;
    }

    public function asPrefix(): BridgeBuilder
    {
        $this->query->setTerm('prefix');

        return $this;
    }

    public function asRange(): BridgeBuilder
    {
        $this->query->setTerm('range');

        return $this;
    }

    public function asRegex(): BridgeBuilder
    {
        $this->query->setTerm('regex');

        return $this;
    }

    public function asTerm(): BridgeBuilder
    {
        $this->query->setTerm('term');

        return $this;
    }

    public function asTerms(): BridgeBuilder
    {
        $this->query->setTerm('terms');

        return $this;
    }

    public function asTermSet(string $term): BridgeBuilder
    {
        $this->query->setTerm('terms_set');

        return $this;
    }

    public function asWildCard(): BridgeBuilder
    {
        $this->query->setTerm('wildcard');

        return $this;
    }

    public function asMatch(): BridgeBuilder
    {
        $this->query->setTerm('match');

        return $this;
    }

    public function asRaw(): BridgeBuilder
    {
        $this->query->setTerm('raw');

        return $this;
    }
}

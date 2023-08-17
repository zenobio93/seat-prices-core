<?php

namespace RecursiveTree\Seat\PricesCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RecursiveTree\Seat\PricesCore\Contracts\Priceable;
use RecursiveTree\Seat\PricesCore\Contracts\PriceProviderBackend;
use RecursiveTree\Seat\PricesCore\Exceptions\PriceProviderException;

/**
 * @property string $name
 * @property string $backend
 * @property array $configuration
 * @property int $id
 */
class PriceProviderInstance extends Model
{
    public $fillable = [
        'name', 'backend', 'configuration'
    ];

    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'configuration' => 'array',
    ];

    /**
     * Public interface to get prices from this price provider
     *
     * @param Collection<Priceable> $items
     * @return Collection<Priceable>
     * @throws PriceProviderException
     */
    //TODO if generic type hints ever become available, use them here
    public function getPrices(Collection $items): Collection
    {
        // look up backend implementation class
        $backends = config('priceproviders.backends');
        if(!array_key_exists($this->name,$backends)){
            throw new PriceProviderException(sprintf('Price provider backend \'%s\' not found in price provider backend registry. Has a plugin been uninstalled?',$this->name));
        }
        $backend_info = $backends[$this->name];

        if(!array_key_exists('backend',$backend_info)) {
            throw new PriceProviderException(sprintf('Backend configuration for \'%s\' is missing a \'backend\' property', $this->name));
        }
        $BackendClass = $backend_info['backend'];

        if(!$BackendClass instanceof PriceProviderBackend){
            throw new PriceProviderException(sprintf('Backend configuration for \'%s\' specifies a backend implementation that doesn\'t implement \'%s\'.', $this->name,PriceProviderBackend::class));
        }

        $backend = new $BackendClass($this->configuration);
        return $backend->getPrices($items);
    }
}
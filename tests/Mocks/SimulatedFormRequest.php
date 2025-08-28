<?php 
namespace Effectra\LaravelModelOperations\Tests\Mocks;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SimulatedFormRequest extends Request
{
    protected array $rules = [];

    protected array $validatedData = [];

    public function __construct(array $data = [], array $rules = [])
    {
        parent::__construct($data);

        $this->rules = $rules;
    }

    /**
     * Manually validate and return validated data.
     *
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validated(): array
    {
        $validator = Validator::make($this->all(), $this->rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->validatedData = $validator->validated();
    }

    /**
     * Get validated data again.
     */
    public function getValidatedData(): array
    {
        return $this->validatedData;
    }

    /**
     * Override rules (optional).
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }
}

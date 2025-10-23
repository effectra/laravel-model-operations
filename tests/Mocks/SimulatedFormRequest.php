<?php 
namespace Effectra\LaravelModelOperations\Tests\Mocks;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

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
        $validatorFactory = new \Illuminate\Validation\Factory(new \Illuminate\Translation\Translator(
            new \Illuminate\Translation\ArrayLoader(), 'en'
        ));
        $validator = $validatorFactory->make($this->all(), $this->rules);

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

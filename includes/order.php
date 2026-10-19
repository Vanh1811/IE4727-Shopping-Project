<?php

/**
 * Shared order input validation and persistence helpers.
 */

function f2bOrderReadInput($input, $key)
{
    if (!is_array($input) || !isset($input[$key]) || !is_scalar($input[$key])) {
        return '';
    }

    return trim((string) $input[$key]);
}

function f2bOrderHasControlCharacters($value)
{
    return preg_match('/[\x00-\x1F\x7F]/', $value) === 1;
}

function f2bOrderValidateCheckout($input)
{
    $values = array(
        'full_name' => f2bOrderReadInput($input, 'full_name'),
        'email' => f2bOrderReadInput($input, 'email'),
        'phone' => f2bOrderReadInput($input, 'phone'),
        'postal_code' => f2bOrderReadInput($input, 'postal_code'),
        'delivery_address' => f2bOrderReadInput($input, 'delivery_address'),
        'special_instructions' => f2bOrderReadInput($input, 'special_instructions'),
    );
    $errors = array();

    if ($values['full_name'] === '' || strlen($values['full_name']) < 2 || strlen($values['full_name']) > 160 || f2bOrderHasControlCharacters($values['full_name'])) {
        $errors['full_name'] = 'Enter your full name.';
    }

    if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false || strlen($values['email']) > 254) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (
        $values['phone'] === '' ||
        strlen($values['phone']) < 7 ||
        strlen($values['phone']) > 30 ||
        preg_match('/^[0-9+(). -]+$/', $values['phone']) !== 1 ||
        preg_match('/[0-9]{7,}/', $values['phone']) !== 1
    ) {
        $errors['phone'] = 'Enter a valid phone number.';
    }

    if (
        $values['postal_code'] === '' ||
        strlen($values['postal_code']) < 3 ||
        strlen($values['postal_code']) > 20 ||
        preg_match('/^[0-9A-Za-z][0-9A-Za-z -]{2,19}$/', $values['postal_code']) !== 1
    ) {
        $errors['postal_code'] = 'Enter a valid postal code.';
    }

    if (
        $values['delivery_address'] === '' ||
        strlen($values['delivery_address']) > 500 ||
        f2bOrderHasControlCharacters($values['delivery_address'])
    ) {
        $errors['delivery_address'] = 'Enter a delivery address up to 500 characters.';
    }

    if (strlen($values['special_instructions']) > 1000 || f2bOrderHasControlCharacters($values['special_instructions'])) {
        $errors['special_instructions'] = 'Special instructions must be 1,000 characters or fewer.';
    }

    return array(
        'values' => $values,
        'errors' => $errors,
    );
}

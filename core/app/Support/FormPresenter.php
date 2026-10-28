<?php

namespace App\Support;

use App\Models\Form;

/**
 * Admin-built forms (Form::form_data, rendered in Blade by <x-viser-form>)
 * as a plain field list for the React <DynamicForm> component.
 * Validation stays on the server (App\Lib\FormProcessor::valueValidation).
 */
class FormPresenter
{
    /**
     * @return list<array{key: string, label: string, type: string, required: bool, instruction: ?string, options: list<string>, extensions: ?string, width: string}>
     */
    public static function fields(?int $formId): array
    {
        $form = $formId ? Form::find($formId) : null;

        $fields = [];
        foreach (@$form->form_data ?? [] as $field) {
            $fields[] = [
                // The request key FormProcessor reads ($request->{label}).
                'key'         => (string) $field->label,
                'label'       => (string) $field->name,
                'type'        => (string) $field->type,
                'required'    => @$field->is_required == 'required',
                'instruction' => @$field->instruction ?: null,
                'options'     => array_values((array) (@$field->options ?? [])),
                'extensions'  => @$field->extensions ?: null,
                'width'       => (string) (@$field->width ?: '12'),
            ];
        }

        return $fields;
    }

    /** Admin-written rich text (gateway / method instructions), with scripts and event handlers removed. */
    public static function safeHtml(?string $html): string
    {
        if (!$html) {
            return '';
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));
        $config->set('HTML.TargetBlank', true);

        return (new \HTMLPurifier($config))->purify($html);
    }
}

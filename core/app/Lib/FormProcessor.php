<?php

namespace App\Lib;

use App\Models\Form;

class FormProcessor
{
    /**
     * Rules for the form builder's fields (form_generator[...][i]). Extensions
     * are checked here: generate() used to throw a plain exception for an
     * unknown one, which showed a server error. Two fields whose labels
     * give the same key (e.g. "Account No" and "account no") would silently
     * overwrite each other, so they are refused too.
     */
    public function generatorValidation()
    {
        $supported = $this->supportedExt();
        $validation['rules'] = [
            'form_generator'               => 'nullable|array',
            'form_generator.is_required.*' => 'required|in:required,optional',
            'form_generator.options.*'     => 'nullable|string|max:2000',
            'form_generator.form_label.*'  => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                $keys = array_map(fn ($l) => titleToKey((string) $l), (array) request()->input('form_generator.form_label', []));
                if (count(array_keys($keys, titleToKey((string) $value))) > 1) {
                    $fail('Two fields have the label "' . $value . '"; each needs its own.');
                }
            }],
            // Checked on the type, which is always sent: a rule on an empty
            // "extensions" value would not run at all
            'form_generator.form_type.*'   => ['required', 'in:text,select,radio,textarea,checkbox,file,email,url,number,datetime,date,time', function ($attribute, $value, $fail) {
                $index = (int) substr($attribute, strrpos($attribute, '.') + 1);
                $ext   = request()->input("form_generator.extensions.$index");
                if ($value === 'file' && ($ext === null || $ext === '' || $ext === 'null')) {
                    $fail('Extensions is required when the form type is file');
                }
                if (in_array($value, ['select', 'radio', 'checkbox'], true) && trim((string) request()->input("form_generator.options.$index")) === '') {
                    $fail('Options are required for a ' . $value . ' field');
                }
            }],
            'form_generator.form_width.*'  => 'required|in:12,6,4,3',
            'form_generator.instruction.*' => 'nullable|string|max:500',
            'form_generator.extensions.*'  => ['nullable', 'string', function ($attribute, $value, $fail) use ($supported) {
                if ($value === null || $value === '' || $value === 'null') {
                    return;
                }
                $bad = array_diff(array_map('trim', explode(',', strtolower($value))), $supported);
                if ($bad) {
                    $fail('Unsupported file type(s): ' . implode(', ', $bad) . '. Allowed: ' . implode(', ', $supported) . '.');
                }
            }],
        ];
        $validation['messages'] = [
            'form_generator.is_required.*.required'=>'All is required field is required',
            'form_generator.is_required.*.in'=>'Is required field is invalid',
            'form_generator.form_label.*.required'=>'All form label is required',
            'form_generator.form_type.*.required'=>'All form type is required',
            'form_generator.form_type.*.in'=>'Some selected form type is invalid',
            'form_generator.form_width.*.required'=>'All form width is required',
            'form_generator.form_width.*.in'=>'Some selected form width is invalid',
        ];
        return $validation;
    }

    /**
     * A saved form as the React form builder's rows
     * (Components/Admin/FormBuilder.tsx).
     */
    public static function builderFields($form): array
    {
        return collect((array) ($form->form_data ?? []))->map(fn ($f) => [
            'label'       => (string) ($f->name ?? ''),
            'type'        => (string) ($f->type ?? 'text'),
            'is_required' => ($f->is_required ?? 'optional') === 'required' ? 'required' : 'optional',
            'width'       => (string) ($f->width ?? '12'),
            'options'     => implode(',', (array) ($f->options ?? [])),
            'extensions'  => (string) ($f->extensions ?? ''),
            'instruction' => (string) ($f->instruction ?? ''),
        ])->values()->all();
    }

    public function generate($act,$isUpdate = false, $identifierField = 'act',$identifier = null)
    {
        $forms = request()->form_generator;
        $formData = [];
        if ($forms) {
            for ($i=0; $i < count($forms['form_label']); $i++) {
                $extensions = $forms['extensions'][$i] ?? null;
                if ($extensions != 'null' && $extensions != null) {
                    $extensionsArr = array_map('trim', explode(',', strtolower($extensions)));
                    $notMatchedExt = count(array_diff($extensionsArr,$this->supportedExt()));
                    if ($notMatchedExt > 0) {
                        throw new \Exception("Your selected extensions are invalid");
                    }
                }
                $label = titleToKey($forms['form_label'][$i]);
                $formData[$label] = [
                    'name' => $forms['form_label'][$i],
                    'label' => $label,
                    'is_required' => $forms['is_required'][$i],
                    'instruction' => @$forms['instruction'][$i],
                    'extensions' => $forms['extensions'][$i] == 'null' ? "" : implode(',', array_filter(array_map('trim', explode(',', strtolower((string) $forms['extensions'][$i]))))),
                    'options' => $forms['options'][$i] ? array_values(array_filter(array_map('trim', explode(",",$forms['options'][$i])), 'strlen')) : [],
                    'type' => $forms['form_type'][$i],
                    'width' => $forms['form_width'][$i],
                ];
            }
        }
        if ($isUpdate) {
            if ($identifierField == 'act') {
                $identifier = $act;
            }
            $form = Form::where($identifierField,$identifier)->first();
            if (!$form) {
                $form = new Form();
            }
        }else{
            $form = new Form();
        }
        $form->act = $act;
        $form->form_data = $formData;
        $form->save();
        return $form;
    }

    public function valueValidation($formData)
    {
        $validationRule = [];
        $rule = [];

        foreach($formData as $data){
            if ($data->is_required == 'required') {
                $rule = array_merge($rule,['required']);
            }else{
                $rule = array_merge($rule,['nullable']);
            }
            if ($data->type == 'select' || $data->type == 'checkbox' || $data->type == 'radio'){
                $rule = array_merge($rule,['in:'. implode(',',$data->options)]);
            }
            if ($data->type == 'file') {
                $rule = array_merge($rule,['mimes:'.$data->extensions]);
            }
            if ($data->type == 'email') {
                $rule = array_merge($rule,['email']);
            }
            if ($data->type == 'url') {
                $rule = array_merge($rule,['url']);
            }
            if ($data->type == 'number') {
                $rule = array_merge($rule,['integer']);
            }
            if ($data->type == 'checkbox') {
                $rule = array_merge($rule,['array']);
                $validationRule[$data->label] = $rule;
            }else{
                $validationRule[$data->label] = $rule;
            }
            $rule = [];
        }
        return $validationRule;
    }

    public function processFormData($request, $formData)
    {
        $requestForm = [];
        foreach($formData as $data){
            $name = $data->label;
            $value = $request->$name;
            if($data->type == 'file') {
                if($request->hasFile($name)){
                    $directory = date("Y")."/".date("m")."/".date("d");
                    $path = getFilePath('verify').'/'.$directory;
                    $value = $directory.'/'.fileUploader($value, $path);
                }else{
                    $value = null;
                }
            }
            $requestForm[] = [
                'name'=>$data->name,
                'type'=>$data->type,
                'value'=>$value,
            ];
        }
        return $requestForm;
    }

    public function supportedExt()
    {
        return [
            'jpg',
            'jpeg',
            'png',
            'pdf',
            'doc',
            'docx',
            'txt',
            'xlx',
            'xls',
            'xlsx',
            'csv',
            'avi',
            'flv',
            'gif',
            'm4a',
            'mp3',
            'mp4',
            'ppsx',
            'rtf',
            'wav',
            'xla',
        ];
    }
}

<?php

namespace PowerComponents\LivewirePowerGrid\Actions;

use Illuminate\Support\Facades\{File, Schema};
use Illuminate\Support\Str;

class FillableTable
{
    /**
    * @throws \Exception
    */
    public static function create(string $modelName, string $modelLastName, string $template = null): string
    {
        /** @var  \Illuminate\Database\Eloquent\Model $model*/
        $model = new $modelName();

        if (!empty($template)) {
            $stub =  File::get(base_path($template));
        } else {
            $stub = File::get(__DIR__ . '/../../resources/stubs/table.fillable.stub');
        }

        $getFillable = $model->getFillable();

        if (filled($model->getKeyName())) {
            $getFillable = array_merge([$model->getKeyName()], $getFillable);
        }

        $getFillable = array_merge(
            $getFillable,
            ['created_at', 'updated_at']
        );

        $datasource = '';
        $columns    = "[\n";

        foreach ($getFillable as $field) {
            if (in_array($field, $model->getHidden())) {
                continue;
            }

            $conn = Schema::getConnection();

            $conn->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->registerDoctrineTypeMapping('enum', 'string');

            if (Schema::hasColumn($model->getTable(), $field)) {
                $column = $conn->getDoctrineColumn($model->getTable(), $field);

                $title = Str::of($field)->replace('_', ' ')->upper();

                if (in_array($column->getType()->getName(), ['datetime', 'date'])) {
                    $columns .= '            Column::add()' . "\n" . '                ->title(\'' . $title . '\')' . "\n" . '                ->field(\'' . $field . '_formatted\', \'' . $field . '\')' . "\n" . '                ->searchable()' . "\n" . '                ->sortable()' . "\n" . '                ->makeInputDatePicker(\'' . $field . '\'),' . "\n\n";
                }

                if ($column->getType()->getName() === 'datetime') {
                    $datasource .= "\n" . '            ->addColumn(\'' . $field . '_formatted\', function(' . $modelLastName . ' $model) { ' . "\n" . '                return Carbon::parse($model->' . $field . ')->format(\'d/m/Y H:i:s\');' . "\n" . '            })';

                    continue;
                }

                if ($column->getType()->getName() === 'date') {
                    $datasource .= "\n" . '            ->addColumn(\'' . $field . '_formatted\', function(' . $modelLastName . ' $model) { ' . "\n" . '                return Carbon::parse($model->' . $field . ')->format(\'d/m/Y\');' . "\n" . '            })';

                    continue;
                }

                if ($column->getType()->getName() === 'boolean') {
                    $datasource .= "\n" . '            ->addColumn(\'' . $field . '\')';
                    $columns    .= '            Column::add()' . "\n" . '                ->title(\'' . $title . '\')' . "\n" . '                ->field(\'' . $field . '\')' . "\n" . '                ->toggleable(),' . "\n\n";

                    continue;
                }

                if (in_array($column->getType()->getName(), ['smallint', 'integer', 'bigint'])) {
                    $datasource .= "\n" . '            ->addColumn(\'' . $field . '\')';
                    $columns    .= '            Column::add()' . "\n" . '                ->title(\'' . $title . '\')' . "\n" . '                ->field(\'' . $field . '\')' . "\n" . '                ->makeInputRange(),' . "\n\n";

                    continue;
                }

                if ($column->getType()->getName() === 'string') {
                    $datasource .= "\n" . '            ->addColumn(\'' . $field . '\')';
                    $columns    .= '            Column::add()' . "\n" . '                ->title(\'' . $title . '\')' . "\n" . '                ->field(\'' . $field . '\')' . "\n" . '                ->sortable()' . "\n" . '                ->searchable()' . "\n" . '                ->makeInputText(),' . "\n\n";

                    continue;
                }

                $datasource .= "\n" . '            ->addColumn(\'' . $field . '\')';
                $columns    .= '            Column::add()' . "\n" . '                ->title(\'' . $title . '\')' . "\n" . '                ->field(\'' . $field . '\')' . "\n" . '                ->sortable()' . "\n" . '                ->searchable(),' . "\n\n";
            }
        }

        $columns .= "        ]\n";

        $stub = str_replace('{{ datasource }}', $datasource, $stub);

        return str_replace('{{ columns }}', $columns, $stub);
    }
}

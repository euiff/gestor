<?php

// Navegue até o diretório do projeto Laravel
chdir(__DIR__);

// Execute o comando de migração do Laravel com a opção --force
$output = shell_exec('php artisan migrate --force 2>&1');

// Traduza as mensagens de migração
$translatedOutput = str_replace(
    [
        'Nothing to migrate.',
        'Migration table created successfully.',
        'Migrated:',
        'Rolling back:',
        'Rolled back:',
        'Migration completed successfully.'
    ],
    [
        'Nenhuma tabela a ser migrada.',
        'Tabela de migração criada com sucesso.',
        'Migrado:',
        'Revertendo:',
        'Revertido:',
        'Migração concluída com sucesso.'
    ],
    $output
);

// Exiba a saída do comando traduzida
echo "<pre>$translatedOutput</pre>";
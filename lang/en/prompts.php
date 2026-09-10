<?php

/*
 * Prompt fragments sent to the model, not UI copy. Kept per-language so an
 * agent can answer in the language its audience actually speaks.
 */
return [
    'language_rule' => 'Answer in English only.',
    'rules_heading' => 'Rules:',
    'context_heading' => 'Context:',
    'rules' => [
        'Use only the context below. Do not invent facts.',
        'Take earlier messages in the conversation into account for follow-up questions.',
        'If the answer is not in the context, say plainly that you could not find the information.',
        'List the sources you used at the end of the answer, in the format [#number].',
    ],
];

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local syllabusworkflow for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['actionsaved'] = 'Operação concluída';
$string['added'] = 'Adicionado';
$string['after'] = 'Depois';
$string['approve'] = 'Aprovar';
$string['approvedplans'] = 'Planos aprovados';
$string['archive'] = 'Arquivar plano de ensino';
$string['assessmentcriteria'] = 'Critérios de avaliação';
$string['assessmenttools'] = 'Instrumentos de avaliação';
$string['author'] = 'Autor';
$string['before'] = 'Antes';
$string['bibliography'] = 'Bibliografia básica';
$string['changes'] = 'Solicitar correções';
$string['comment'] = 'Comentário ou justificativa';
$string['compare'] = 'Comparar revisões';
$string['competencies'] = 'Competências e habilidades';
$string['contents'] = 'Conteúdo programático';
$string['copylink'] = 'Copiar link do curso';
$string['courseidentification'] = 'Identificação do curso';
$string['createplan'] = 'Criar plano de ensino';
$string['defaulttemplate'] = 'Plano institucional padrão';
$string['delegate'] = 'Delegar revisão';
$string['disciplinecode'] = 'Código da disciplina';
$string['disciplinename'] = 'Nome da disciplina';
$string['downloadhtml'] = 'Baixar HTML';
$string['editplan'] = 'Editar plano de ensino';
$string['eventsyllabuschanged'] = 'Estado do plano de ensino alterado';
$string['export'] = 'HTML para impressão';
$string['furtherbibliography'] = 'Bibliografia complementar';
$string['generalobjectives'] = 'Objetivos gerais';
$string['hash'] = 'SHA-256 do conteúdo';
$string['history'] = 'Histórico de versões';
$string['identityjson'] = 'Identidade institucional (JSON)';
$string['institutionalnotes'] = 'Observações institucionais';
$string['invalidaction'] = 'Operação desconhecida';
$string['invaliddecision'] = 'Decisão inválida ou justificativa ausente';
$string['invalidfield'] = 'Campo inválido: {$a}';
$string['invalidreviewer'] = 'O revisor selecionado precisa ter permissão para aprovar';
$string['invalidrevision'] = 'Não é possível criar uma revisão neste estado';
$string['invalidschema'] = 'Estrutura de campos institucionais inválida';
$string['invalidstages'] = 'Etapas de aprovação inválidas';
$string['invalidtransition'] = 'Transição de estado inválida';
$string['lockedfield'] = 'Campo não pode ser alterado: {$a}';
$string['lockfailed'] = 'Outra operação bloqueou este plano';
$string['messageapproved'] = 'Plano aprovado';
$string['messagebody'] = 'Um plano de ensino precisa de sua atenção. Acesse o Moodle para ver os detalhes.';
$string['messagechangesrequested'] = 'Correções solicitadas em um plano';
$string['messageduedate'] = 'Lembrete de prazo de revisão';
$string['messageprovider:approved'] = 'Plano de ensino aprovado';
$string['messageprovider:changesrequested'] = 'Correções solicitadas no plano de ensino';
$string['messageprovider:duedate'] = 'Prazo de revisão de plano de ensino';
$string['messageprovider:published'] = 'Plano de ensino publicado';
$string['messageprovider:submitted'] = 'Plano de ensino enviado para revisão';
$string['messagepublished'] = 'Plano publicado';
$string['messagesubmitted'] = 'Plano enviado para revisão';
$string['methodology'] = 'Metodologia de ensino';
$string['models'] = 'Modelos institucionais';
$string['modified'] = 'Modificado';
$string['newrevision'] = 'Criar nova revisão';
$string['noplan'] = 'Não existe plano de ensino para este curso';
$string['notpublished'] = 'Não há uma versão publicada disponível para consulta';
$string['pending'] = 'Planos pendentes';
$string['period'] = 'Período letivo';
$string['pluginname'] = 'Fluxo de planos de ensino';
$string['privacy:actor'] = 'Usuário responsável pela operação';
$string['privacy:comment'] = 'Comentário ou justificativa registrada por um usuário';
$string['privacy:identityjson'] = 'Identidade institucional associada ao modelo';
$string['privacy:templates'] = 'Revisões dos modelos institucionais';
$string['privacy:workflowjson'] = 'Revisores configurados nas etapas de aprovação institucional';
$string['privacy:createdby'] = 'Criador do plano ou da revisão';
$string['privacy:formeruser'] = 'Usuário removido';
$string['privacy:history'] = 'Histórico institucional de auditoria';
$string['privacy:messages'] = 'Notificações de mensagens';
$string['privacy:payload'] = 'Conteúdo do plano de ensino';
$string['privacy:plans'] = 'Metadados do plano';
$string['privacy:recipient'] = 'Destinatário da mensagem';
$string['privacy:snapshot'] = 'Identificação congelada do curso e autor';
$string['privacy:versions'] = 'Versões dos planos';
$string['publish'] = 'Publicar';
$string['readonlyversion'] = 'Somente rascunhos podem ser editados';
$string['removed'] = 'Removido';
$string['reports'] = 'Relatórios';
$string['requiredfield'] = 'Campo obrigatório vazio: {$a}';
$string['resources'] = 'Recursos didáticos';
$string['review'] = 'Iniciar revisão';
$string['reviewerid'] = 'ID do usuário revisor';
$string['revision'] = 'Revisão';
$string['saved'] = 'Rascunho salvo';
$string['savedraft'] = 'Salvar rascunho';
$string['schedule'] = 'Cronograma de atividades';
$string['schemajson'] = 'Campos e regras (JSON)';
$string['selfapproval'] = 'Você não pode aprovar seu próprio plano';
$string['sendfailed'] = 'Falha ao enviar mensagem';
$string['specificobjectives'] = 'Objetivos específicos';
$string['staleversion'] = 'Revisão alterada por outra pessoa ou não mais atual';
$string['state_approved'] = 'Aprovado';
$string['state_archived'] = 'Arquivado';
$string['state_changes_requested'] = 'Correções solicitadas';
$string['state_draft'] = 'Rascunho';
$string['state_published'] = 'Publicado';
$string['state_submitted'] = 'Enviado';
$string['state_under_review'] = 'Em revisão';
$string['status'] = 'Situação';
$string['submit'] = 'Enviar para revisão';
$string['syllabus'] = 'Ementa';
$string['syllabusworkflow:approve'] = 'Aprovar planos de ensino';
$string['syllabusworkflow:edit'] = 'Criar e editar planos de ensino';
$string['syllabusworkflow:manage'] = 'Administrar o fluxo de planos de ensino';
$string['syllabusworkflow:publish'] = 'Publicar planos de ensino aprovados';
$string['syllabusworkflow:review'] = 'Revisar planos de ensino enviados';
$string['syllabusworkflow:selfapprove'] = 'Aprovar o próprio plano quando permitido';
$string['syllabusworkflow:view'] = 'Visualizar planos de ensino publicados';
$string['taskduedates'] = 'Notificar prazos pendentes dos revisores';
$string['teacher'] = 'Professor responsável';
$string['templatename'] = 'Nome do modelo';
$string['term'] = 'Período letivo';
$string['transitions'] = 'Histórico de decisões';
$string['viewplan'] = 'Ver plano de ensino';
$string['workflowjson'] = 'Etapas de aprovação (JSON)';
$string['workload'] = 'Carga horária';
$string['wrongplan'] = 'A revisão não pertence ao plano solicitado';

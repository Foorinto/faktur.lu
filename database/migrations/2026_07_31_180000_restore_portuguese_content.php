<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RESTAURATION des versions portugaises écrasées par le seeder.
 *
 * Diagnostic. deploy.sh lance une chaîne de seeders après les migrations.
 * BlogPostsPortugueseSeeder utilisait updateOrCreate : à chaque
 * déploiement, il réécrivait le contenu portugais de tous les articles
 * qu il couvre, annulant silencieusement les corrections apportées
 * ensuite par migration. Quinze articles corrigés ont ainsi perdu leur
 * version portugaise à chaque mise en production, sans aucune erreur
 * remontée — les autres langues n étaient pas touchées, ce qui rendait
 * le symptôme difficile à repérer.
 *
 * Le seeder a été corrigé dans le même commit : il ne crée désormais que
 * les lignes absentes, comme le font déjà les seeders
 * BlogScheduledSummer2026*. Il garde son utilité pour amorcer une base
 * neuve sans figer le contenu d une base en service.
 *
 * Cette migration réapplique le contenu portugais correct des articles
 * concernés. Le contenu est celui de la base de référence, issu des
 * migrations de correction déjà validées.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (self::CONTENT as $key => $content) {
            DB::table("blog_posts")
                ->where("translation_key", $key)
                ->where("locale", "pt")
                ->update(["content" => $content, "updated_at" => now()]);
        }
    }

    public function down(): void
    {
        // Restauration de contenu : rien à défaire.
    }

    private const CONTENT = [
        "archivage-factures-luxembourg-duree-legale-format" => <<<'PTBODY'
<p class="lead">O arquivo das faturas é uma obrigação legal no Luxemburgo: <strong>dez anos</strong>. Mas a questão do <em>início</em> desse prazo, e a do <em>local</em> onde os arquivos podem residir, são mais precisas do que se julga. Eis o que diz exatamente o artigo 65 da lei do IVA.</p>

<h2>Prazo legal de conservação</h2>

<h3>Faturas: dez anos a contar da data de emissão</h3>

<p>O <strong>artigo 65.º, parágrafo 4</strong> da <a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">lei do IVA</a> é explícito:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    «Essas faturas e cópias de faturas devem ser conservadas durante um período de dez anos <strong>a contar da sua data de emissão</strong>.»
</blockquote>

<p>Uma fatura emitida a <strong>15 de março de 2026</strong> deve, portanto, ser conservada até <strong>15 de março de 2036</strong> — e não até ao final de 2036. A regra vale tanto para as faturas que emite como para as que recebe.</p>

<h3>Livros e outros documentos</h3>

<p>Para os restantes documentos, o ponto de partida muda:</p>

<ul>
    <li><strong>Livros contabilísticos</strong>: dez anos a contar do seu <strong>encerramento</strong></li>
    <li><strong>Outros documentos</strong>: dez anos a contar da sua <strong>data</strong></li>
    <li><strong>Registos das interfaces eletrónicas</strong> (marketplaces, plataformas): dez anos a contar de <strong>31 de dezembro do ano da operação</strong></li>
</ul>

<p>Acresce a obrigação geral do <strong>Código Comercial</strong> (artigo 16), que impõe igualmente dez anos para os livros e a correspondência comercial.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Uma confusão frequente</p>
    <p>Muitos guias fazem correr o prazo das faturas a partir do fim do exercício. É a regra dos <strong>livros</strong>, não a das faturas. Na prática, alinhar todas as suas eliminações pelo fim do exercício leva-o a conservar um pouco mais do que o necessário: é prudente, mas não é a regra.</p>
</div>

<h2>Onde pode armazenar os seus arquivos?</h2>

<p>É a questão que qualquer serviço em linha levanta, e o artigo 65.º responde-lhe no parágrafo 6.</p>

<h3>O princípio</h3>

<p>Determina <strong>livremente o local de armazenamento</strong>, com uma condição: colocar à disposição da administração, «<strong>sem demora indevida, a qualquer pedido seu</strong>», todas as faturas, informações, livros e documentos em causa.</p>

<h3>Os limites</h3>

<ul>
    <li><strong>Países sem assistência mútua</strong>: é proibido armazenar num país ou território com o qual não exista qualquer instrumento de assistência mútua de alcance comparável, nem direito de acesso eletrónico</li>
    <li><strong>Arquivos em papel</strong>: um sujeito passivo estabelecido no Luxemburgo deve aí armazenar as suas faturas <strong>quando o armazenamento não é eletrónico</strong> com acesso completo e em linha. Concretamente, os seus dossiês ficam no Luxemburgo</li>
    <li><strong>Declaração obrigatória</strong>: se o local de armazenamento estiver <strong>fora do território luxemburguês</strong>, deve declará-lo à administração — na <strong>declaração anual</strong> prevista no artigo 64.º, parágrafo 7</li>
    <li><strong>Armazenamento noutro Estado-Membro</strong>: deve garantir aos agentes da administração um direito de <strong>acesso por via eletrónica, descarregamento e utilização</strong> dessas faturas</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar se utiliza um serviço em linha</p>
    <p>Pergunte ao seu prestador <strong>onde estão fisicamente alojados os dados</strong>. Se os servidores estiverem fora do Luxemburgo, aplica-se a obrigação de declaração na sua declaração anual — mesmo tratando-se de um serviço europeu. É uma formalidade simples, mas fácil de esquecer.</p>
</div>

<h2>Formatos de arquivo aceites</h2>

<p>O artigo 65.º, parágrafo 5, estabelece a exigência de fundo: a <strong>autenticidade da origem</strong>, a <strong>integridade do conteúdo</strong> e a <strong>legibilidade</strong> devem ser asseguradas durante todo o período de conservação.</p>

<h3>Arquivo em papel</h3>
<p>Os originais em papel devem ser conservados num local seco e acessível, sem alteração e — como visto acima — em território luxemburguês.</p>

<h3>Arquivo digital</h3>
<p>O armazenamento eletrónico é válido «desde que os dados que garantem a autenticidade da origem e a integridade do conteúdo sejam igualmente armazenados sob forma eletrónica». Na prática:</p>

<ul>
    <li>O formato deve garantir a <strong>integridade</strong> do documento</li>
    <li>O documento deve manter-se <strong>legível</strong> durante todo o prazo de conservação</li>
    <li>O formato <strong>PDF/A</strong> (ISO 19005) é recomendado para a legibilidade a longo prazo</li>
    <li>Uma <strong>impressão digital</strong> (hash) permite demonstrar que o documento não foi alterado</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Digitalização e valor probatório</p>
    <p>Para que uma cópia eletrónica tenha o mesmo <strong>valor probatório</strong> que o original em papel, a <a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">lei de 25 de julho de 2015</a> faz assentar essa presunção no recurso a um <a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">PSDC</a> (prestador de serviços de desmaterialização ou de conservação) certificado. Digitalizar sem PSDC continua possível, mas a cópia não beneficia dessa presunção: em caso de contestação, compete-lhe demonstrar a sua fidelidade.</p>
</div>

<h2>Porquê o formato PDF/A?</h2>

<p>O <strong>PDF/A</strong> é uma norma ISO concebida para o arquivo a longo prazo. Ao contrário de um PDF normal:</p>

<ul>
    <li><strong>Incorpora todos os tipos de letra</strong> utilizados (sem dependência externa)</li>
    <li><strong>Proíbe o JavaScript</strong> e os elementos multimédia</li>
    <li>Visa garantir que o documento permanecerá <strong>legível dentro de dez, vinte ou cinquenta anos</strong></li>
    <li>É <strong>amplamente reconhecido</strong> pelas administrações europeias</li>
</ul>

<h2>Arquivar as faturas com o faktur.lu</h2>

<p>O faktur.lu propõe um arquivo dedicado, disponível nos planos que incluem a funcionalidade:</p>

<ol>
    <li>Arquiva uma fatura <strong>finalizada</strong> — individualmente ou em lote</li>
    <li>O documento é convertido em <strong>PDF/A-1b</strong> por defeito; o <strong>PDF/A-3b</strong> está disponível se precisar de incorporar anexos</li>
    <li>É calculada e registada uma <strong>impressão SHA-256</strong>, o que permite verificar depois a integridade do arquivo</li>
    <li>É registado com o arquivo um <strong>prazo de dez anos</strong></li>
    <li>Pode descarregar os seus arquivos a qualquer momento</li>
</ol>

<p class="text-sm text-slate-500"><em>Precisão técnica: a conversão em PDF/A apoia-se no Ghostscript do lado do servidor. Se a ferramenta não estiver disponível, o documento é conservado em PDF normal com a sua impressão — o formato efetivamente obtido é registado com o arquivo, pelo que sabe sempre o que tem.</em></p>

<h2>Riscos em caso de não conformidade</h2>

<p>Numa inspeção fiscal, a ausência de faturas ou um arquivo não conforme pode acarretar:</p>

<ul>
    <li>A <strong>rejeição das deduções de IVA</strong> sobre as faturas em falta</li>
    <li><strong>Coimas administrativas</strong> (250 € a 10 000 € por infração, art. 77 LIVA) e até <strong>25 000 € por dia de atraso</strong> após advertência, em caso de recusa de apresentação dos documentos durante uma inspeção</li>
    <li>Uma <strong>tributação oficiosa</strong> pela administração</li>
</ul>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Lei do IVA coordenada - artigo 65.º (conservação e armazenamento)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Obrigações contabilísticas das empresas</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Lei de 25 de julho de 2015 relativa ao arquivo eletrónico</a></li>
    <li><a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">ILNAS - Prestadores PSDC certificados</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">ficheiro FAIA →</a></li><li><a href="/pt/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">inspeção fiscal →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Arquivo PDF/A com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">Arquive as suas faturas finalizadas em PDF/A com impressão SHA-256 e prazo de dez anos, individualmente ou em lote.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>As regras de conservação podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
PTBODY,

        "choisir-logiciel-facturation-luxembourg-comparatif" => <<<'PTBODY'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Em resumo</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li><strong>6 critérios</strong> para escolher: conformidade LU (menções art. 63, numeração, 4 taxas de IVA, isenção 50 000 €), ciclo completo (orçamentos, notas de crédito, faturas recorrentes), simplicidade, preço tudo incluído, segurança (alojamento na UE, 2FA), integração com o contabilista.</li><li>O <strong>FAIA</strong> não é exigível a todos: aplicam-se quatro condições cumulativas. Continua, ainda assim, a ser o critério que o protege <strong>quando crescer</strong>.</li><li>Cuidado com os preços de chamariz: compare o que está <strong>realmente incluído</strong>, não apenas o valor anunciado.</li></ul></div>
<p class="lead">Escolher um software de faturação sendo independente ou PME no Luxemburgo não é escolher mais «uma ferramenta de faturas». É escolher uma ferramenta que o coloca <strong>em conformidade com regras muito específicas do Grão-Ducado</strong> (menções obrigatórias, IVA, numeração, arquivo). Eis os 6 critérios que realmente contam, e como avaliá-los.</p>

<h2>Porque é que o Luxemburgo muda tudo</h2>
<p>Um software de faturação «genérico» (francês, belga, internacional) cumpre muitas vezes o básico mas ignora as obrigações luxemburguesas. Ora a Administração do registo, dos domínios e do IVA (AED) pode exigir, numa inspeção, menções legais exatas, uma numeração irrepreensível e — sob certas condições — um ficheiro de auditoria num formato preciso. O bom software deve, portanto, ser pensado <strong>para</strong> o Luxemburgo, e não apenas estar «disponível» no Luxemburgo.</p>

<h2>Critério 1: a conformidade luxemburguesa</h2>
<p>É o critério central. Verifique com precisão:</p>
<ul>
    <li><strong>Menções obrigatórias (artigo 63 LIVA)</strong>: dados, número de IVA, número sequencial único, taxas e montantes — aplicados automática e corretamente.</li>
    <li><strong>Numeração sequencial contínua</strong>: sem falhas nem duplicados, bloqueada assim que a fatura é finalizada.</li>
    <li><strong>As 4 taxas de IVA luxemburguesas</strong>: 17 % (normal), 14 % (intermédia), 8 % (reduzida) e 3 % (super-reduzida), aplicadas ao cenário correto.</li>
    <li><strong>Regime de isenção</strong>: gestão do limiar de <strong>50 000 €</strong> (art. 57bis LIVA) com a menção adequada e um alerta ao aproximar-se do limiar.</li>
    <li><strong>Cenários de IVA internacionais</strong>: autoliquidação intra-UE, exportação, isenção — com a menção legal correta aplicada automaticamente.</li>
    <li><strong>Arquivo de 10 anos</strong>, num formato pensado para a legibilidade a longo prazo (PDF/A) e com uma impressão de integridade.</li>
    <li><strong>Exportação FAIA 2.01</strong>: ver abaixo, o critério é mais matizado do que se costuma dizer.</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">O FAIA não diz respeito a toda a gente</p>
    <p>Muitos comparativos — incluindo, até agora, esta página — apresentam a exportação FAIA como eliminatória. É excessivo. Segundo as FAQ da AED, a obrigação pressupõe <strong>quatro condições reunidas ao mesmo tempo</strong>: estar sujeito ao plano de contas normalizado, não beneficiar de um regime simplificado, ultrapassar 112 000 € de volume de negócios e ultrapassar cerca de 500 transações contabilísticas por ano. Muitos independentes não estão hoje abrangidos.</p>
    <p class="mt-2">Isso não torna o critério inútil: torna-se determinante <strong>no dia em que a sua atividade ultrapassa esses limiares</strong>, e mudar de ferramenta nesse momento custa bem mais do que tê-lo previsto. Ver o nosso <a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600">guia completo do FAIA</a>.</p>
</div>

<h2>Critério 2: as funcionalidades essenciais</h2>
<p>Para além da fatura, a sua atividade precisa de um ciclo completo: <strong>orçamentos</strong> (convertíveis em fatura), <strong>notas de crédito</strong>, <strong>faturas de adiantamento</strong> (com a exigibilidade de IVA correta), <strong>faturas recorrentes</strong>, acompanhamento das <strong>despesas</strong> e do <strong>IVA dedutível</strong>, gestão multimoeda e <strong>lembretes de pagamento</strong>. Quanto mais o software cobrir o ciclo, menos anda a saltar entre ferramentas.</p>

<h2>Critério 3: a simplicidade de utilização</h2>
<p>Não é contabilista: o software deve poupar-lhe tempo, não custá-lo. Teste o <strong>tempo até emitir a primeira fatura</strong> conforme, a clareza da interface e a disponibilidade em português (e idealmente na língua dos seus clientes). Uma boa ferramenta usa-se sem formação.</p>

<h2>Critério 4: um preço adequado à sua atividade</h2>
<p>Desconfie dos preços de chamariz: olhe para o que está <strong>realmente incluído</strong>. Um plano «barato» que cobra à parte as exportações contabilísticas ou o número de faturas pode sair mais caro do que um pacote tudo incluído. Verifique a existência de uma oferta gratuita para começar e a transparência dos escalões.</p>

<h2>Critério 5: segurança e conformidade RGPD</h2>
<p>Os seus dados de clientes e financeiros são sensíveis. Exija <strong>alojamento europeu</strong> (RGPD), cifragem, autenticação de dois fatores, <strong>cópias de segurança regulares</strong> e um isolamento estrito dos dados entre contas. Um alojamento fora da UE é um sinal de alerta — e, quanto aos seus arquivos de faturas, obriga-o a declarar o local de armazenamento à administração.</p>

<h2>Critério 6: a integração com o seu contabilista</h2>
<p>O verdadeiro ganho de tempo (e de dinheiro) joga-se com o seu contabilista. Um software que exporta dados limpos e integráveis (<strong>FAIA 2.01, Sage BOB 50, Sage 100, CSV</strong>) — ou até um <strong>portal dedicado</strong> onde o contabilista consulta os seus dados em leitura — evita-lhe a reintrodução manual e reduz os seus honorários.</p>

<h2>E a faturação eletrónica?</h2>
<p>Dois prazos distintos, muitas vezes confundidos:</p>
<ul>
    <li><strong>B2G (setor público luxemburguês)</strong>: a faturação eletrónica já é obrigatória para faturar ao Estado e aos municípios. Se trabalha com o setor público, é uma necessidade imediata.</li>
    <li><strong>ViDA (reforma europeia)</strong>: o calendário é faseado — extensão do balcão OSS em janeiro de 2027, autoliquidação obrigatória e regras das plataformas em julho de 2028, e depois faturação eletrónica e declaração digital obrigatórias para as operações intra-UE B2B a 1 de julho de 2030. São <strong>2027 e 2028</strong> que chegam primeiro, não 2030.</li>
</ul>
<p>Pergunte então ao seu fornecedor não «faz Peppol?» mas «<strong>para quando, e com que âmbito</strong>?». Um projeto datado vale mais do que uma caixa assinalada.</p>

<h2>Grelha de comparação rápida</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Critério</th><th class="text-left p-2 bg-slate-100">O que exigir</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Conformidade LU</td><td class="p-2 border-b">Menções art. 63, numeração, 4 taxas de IVA, isenção 50 000 €</td></tr>
        <tr><td class="p-2 border-b">Ciclo completo</td><td class="p-2 border-b">Orçamentos, notas de crédito, adiantamentos, recorrentes, despesas, lembretes</td></tr>
        <tr><td class="p-2 border-b">FAIA 2.01</td><td class="p-2 border-b">Disponível no dia em que ultrapassar os limiares</td></tr>
        <tr><td class="p-2 border-b">Faturação eletrónica</td><td class="p-2 border-b">B2G hoje; roteiro datado para o ViDA</td></tr>
        <tr><td class="p-2 border-b">Arquivo</td><td class="p-2 border-b">PDF/A, 10 anos, impressão de integridade</td></tr>
        <tr><td class="p-2 border-b">Segurança</td><td class="p-2 border-b">Alojamento na UE, 2FA, cópias de segurança</td></tr>
        <tr><td class="p-2 border-b">Contabilista</td><td class="p-2 border-b">Exportações FAIA/Sage/CSV, portal do contabilista</td></tr>
        <tr><td class="p-2 border-b">Preço</td><td class="p-2 border-b">Tudo incluído, oferta gratuita, transparência</td></tr>
    </tbody>
</table>

<h2>Excel ou software: vale mesmo a pena dar o passo?</h2>
<p>O Excel parece gratuito, mas não garante nem a numeração contínua nem as menções legais, e não produz um FAIA no dia em que este passar a ser exigível. A partir do momento em que emite mais do que algumas faturas por mês, um software conforme poupa tempo e reduz o risco. Detalhamos esta comparação no nosso artigo <a href="/pt/blog/excel-vs-logiciel-facturation-pourquoi-switch">Excel vs software de faturação</a>.</p>

<h2>Onde está o faktur.lu nestes critérios</h2>
<p>O faktur.lu foi concebido <strong>especificamente para o Luxemburgo</strong>. O que está disponível hoje:</p>
<ul>
    <li>Menções do artigo 63 e numeração sequencial automáticas, as 4 taxas de IVA, cenários intra-UE e de exportação</li>
    <li>Gestão da isenção a 50 000 € com alerta ao aproximar-se do limiar</li>
    <li>Exportação <strong>FAIA 2.01</strong> e exportações contabilísticas <strong>Sage BOB 50, Sage 100, CSV</strong></li>
    <li><strong>Portal do contabilista</strong> em modo de leitura</li>
    <li>Arquivo <strong>PDF/A</strong> com impressão SHA-256 e prazo de dez anos</li>
    <li>Alojamento europeu, autenticação de dois fatores, isolamento de dados por conta</li>
    <li>Interface em <strong>5 línguas</strong> (FR, DE, EN, LB, PT) e <strong>oferta gratuita para começar</strong></li>
</ul>
<p>O que está <strong>em curso</strong>: a entrada em produção do envio Peppol para todas as contas e, depois, a receção de faturas de fornecedores. O formato está tecnicamente pronto, a implementação ainda não — preferimos escrevê-lo a assinalar uma caixa.</p>

<h2>FAQ — escolher o software de faturação no Luxemburgo</h2>
<h3>Um software de faturação é obrigatório no Luxemburgo?</h3>
<p>Não, mas as suas faturas devem respeitar as obrigações legais (menções, numeração, IVA, arquivo). Um software conforme é a forma mais simples de garantir tudo isso.</p>
<h3>O ficheiro FAIA é exigível a todos?</h3>
<p>Não. As FAQ da AED estabelecem quatro condições cumulativas: plano de contas normalizado, ausência de regime simplificado, volume de negócios superior a 112 000 € e cerca de 500 transações contabilísticas por ano. Se faltar uma, não está abrangido — mas a ferramenta deve acompanhar o seu crescimento.</p>
<h3>É possível faturar com Excel no Luxemburgo?</h3>
<p>Nada o proíbe formalmente, mas o Excel não garante nem a numeração contínua nem as menções obrigatórias. O risco numa inspeção é real assim que a atividade cresce.</p>
<h3>Quanto custa um software de faturação?</h3>
<p>De gratuito (ofertas limitadas) a cerca de 15-30 €/mês para uma solução completa. O bom reflexo: comparar o que está <strong>incluído</strong> e não apenas o preço de entrada.</p>
<h3>Que software escolher se estiver em isenção de IVA?</h3>
<p>Escolha uma ferramenta que gere o regime de isenção (limiar 50 000 €), acrescente automaticamente a menção adequada e o <strong>alerte ao aproximar-se do limiar</strong>. Verifique também se contempla as <strong>obrigações residuais</strong>: assim que fatura serviços a profissionais da UE, uma declaração anual simplificada e mapas recapitulativos continuam obrigatórios.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Um software pensado para o Luxemburgo, gratuito para começar</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera faturas conformes (menções, IVA, numeração) e as exportações para o seu contabilista — em poucos minutos.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 31 de julho de 2026.</em></p>
PTBODY,

        "controle-fiscal-luxembourg-comment-preparer" => <<<'PTBODY'
<p class="lead">Receber uma notificação de inspeção da <strong>Administração do registo, dos domínios e do IVA (AED)</strong> inquieta qualquer independente ou dirigente de PME no Luxemburgo. No entanto, com boa preparação, uma inspeção resolve-se em poucas horas. Eis como preparar-se — e uma regra de prescrição que quase toda a gente repete errada.</p>

<h2>Quem é abrangido por uma inspeção da AED?</h2>

<p>Qualquer empresa registada para efeitos de IVA no Luxemburgo pode ser inspecionada. Na prática, a administração visa:</p>

<ul>
    <li><strong>Empresas em crescimento rápido</strong> cujo volume de negócios flutua de forma invulgar</li>
    <li><strong>Setores de risco</strong> identificados pela AED (comércio, restauração, construção, consultoria)</li>
    <li><strong>Declarações de IVA atípicas</strong> (crédito de IVA recorrente, operações intra-UE significativas)</li>
    <li><strong>Inspeções aleatórias</strong> segundo critérios estatísticos</li>
</ul>

<h2>Até que ponto pode a AED recuar?</h2>

<p>É o ponto mais mal compreendido do tema, e o erro está em toda a parte — incluindo, até há pouco, nesta página. O <strong>artigo 81 da lei do IVA</strong> é, porém, inequívoco:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>«A ação do Tesouro para pagamento do imposto e das coimas prescreve em <strong>cinco anos</strong> a contar de 31 de dezembro do ano em que a quantia a cobrar se tornou exigível.»</p>
</blockquote>

<p>Em matéria de <strong>IVA</strong>, a prescrição é portanto de <strong>cinco anos</strong>. A expressão «dez anos» surge em toda a lei do IVA apenas para a <em>conservação</em> de documentos — nunca para a prescrição.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Não confunda IVA com impostos diretos</p>
    <p class="text-red-700">A prescrição alargada para <strong>dez anos</strong> em caso de não declaração ou declaração inexata existe de facto — mas para os <strong>impostos diretos</strong> (§144 AO e art. 10 da lei de 27 de novembro de 1933), administrados pela ACD. <strong>Não</strong> se aplica ao IVA, que compete à AED. Acreditar no contrário pode levá-lo a aceitar uma correção relativa a anos já prescritos.</p>
</div>

<p>Duas nuances, ainda assim. A prescrição pode ser <strong>interrompida</strong> (art. 2244.º e seguintes do Código Civil, ou renúncia do sujeito passivo): corre então um novo prazo, completado no fim do quarto ano seguinte ao último ato interruptivo. E corre a partir de <strong>31 de dezembro</strong> do ano de exigibilidade, não da data da operação.</p>

<p>Por fim, prescrição e arquivo são coisas distintas: deve conservar as suas faturas <strong>dez anos</strong> (art. 65 LIVA e art. 16 do Código Comercial), independentemente do prazo de prescrição de cinco anos.</p>

<h2>Os 3 tipos de inspeção da AED</h2>

<h3>1. Inspeção documental</h3>

<p>A AED pede-lhe que envie documentos por correio ou por via eletrónica. Sem visita presencial. É o tipo mais frequente e menos pesado.</p>

<h3>2. Inspeção no local</h3>

<p>Um agente desloca-se às suas instalações para verificar livros, faturas e comprovativos de despesas. Anunciada por carta — a lei do IVA não fixa prazo de pré-aviso, a AED marca o encontro caso a caso.</p>

<h3>3. Inspeção inopinada</h3>

<p>Rara, reservada a suspeitas de fraude grave. O agente apresenta-se sem aviso. Pode pedir a presença do seu contabilista.</p>

<h2>Documentos que a AED pode pedir</h2>

<ul>
    <li><strong>Todas as faturas emitidas e recebidas</strong> no período inspecionado, com as menções obrigatórias do art. 63 LIVA</li>
    <li><strong>O ficheiro FAIA</strong> no formato 2.01 — para os sujeitos passivos obrigados a produzi-lo, ver abaixo</li>
    <li><strong>O livro de receitas e despesas</strong> ou a contabilidade completa consoante o regime</li>
    <li><strong>As declarações de IVA</strong> entregues, periódicas e anuais</li>
    <li><strong>Os extratos bancários</strong> profissionais do período</li>
    <li><strong>Os comprovativos das despesas deduzidas</strong> (notas de despesa, faturas de fornecedores)</li>
    <li><strong>Os contratos</strong> significativos com clientes e fornecedores</li>
    <li><strong>A prova da autoliquidação</strong> no B2B intra-UE: números de IVA validados no VIES (art. 17 LIVA, art. 196.º da diretiva)</li>
</ul>

<h2>FAIA: o ponto crítico — mas não para todos</h2>

<p>O <strong>FAIA</strong> é um ficheiro XML estruturado, derivado do padrão SAF-T, que reúne o cabeçalho da empresa, as faturas de venda do período, os lançamentos associados e os totais de controlo. O esquema em vigor é a versão <strong>2.01</strong>, cuja última atualização publicada pela AED data de julho de 2020.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Quatro condições cumulativas</p>
    <p>O FAIA não diz respeito a todos os sujeitos passivos. Segundo as FAQ da AED, a obrigação pressupõe reunir <strong>ao mesmo tempo</strong>: estar sujeito ao plano de contas normalizado (PCN), não beneficiar de um regime simplificado, ultrapassar 112 000 € de volume de negócios e ultrapassar cerca de <strong>500 transações contabilísticas</strong> por ano. Uma transação é uma cadeia inteira de lançamentos, não uma fatura. Se faltar uma só condição, não está abrangido — ver o nosso <a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide">guia completo do FAIA</a>.</p>
</div>

<p>Se estiver abrangido e faturar numa folha de cálculo, terá de reconstituir o ficheiro à mão: vários dias de trabalho e um risco elevado de erro.</p>

<h2>Como decorre uma inspeção no local</h2>

<ol>
    <li><strong>Notificação escrita</strong> da AED, em geral por carta registada</li>
    <li><strong>Chegada dos agentes</strong> munidos de uma credencial de inspeção</li>
    <li><strong>Pedido dos documentos</strong> segundo uma lista pré-estabelecida</li>
    <li><strong>Verificação dos lançamentos</strong> e cruzamento com as declarações de IVA</li>
    <li><strong>Entrevista</strong> sobre operações fora do padrão ou desvios detetados</li>
    <li><strong>Notificação das conclusões</strong> por carta</li>
</ol>

<h2>Os 5 erros que saem caro</h2>

<ol>
    <li><strong>Numeração não sequencial das faturas</strong> (art. 63 LIVA, ponto 3°): falhas ou duplicados valem presunção de omissão de volume de negócios</li>
    <li><strong>Menções obrigatórias em falta</strong> nas faturas (art. 63 LIVA)</li>
    <li><strong>Autoliquidação intra-UE não documentada</strong>: sem validação VIES do número do cliente, a AED pode requalificar a operação como tributável no Luxemburgo</li>
    <li><strong>Comprovativos de despesas em falta</strong> ou ilegíveis</li>
    <li><strong>Incoerências entre declarações de IVA e faturas</strong>: mesmo um desvio mínimo desencadeia uma verificação aprofundada</li>
</ol>

<h2>Que sanções em caso de erro?</h2>

<ul>
    <li><strong>Incumprimentos formais</strong> (menção em falta, FAIA não fornecido, declaração tardia): coima administrativa de <strong>250 € a 10 000 € por infração</strong> (art. 77 LIVA)</li>
    <li><strong>Incumprimento que privou o Estado de receita</strong>: coima de <strong>10 % a 50 % do IVA em causa</strong> — proporcional, logo sem limite máximo, e bem mais pesada do que a anterior num litígio significativo</li>
    <li><strong>Recusa de apresentar faturas e documentos contabilísticos</strong>: até <strong>25 000 € por dia de atraso</strong>, após advertência</li>
    <li><strong>Fraude fiscal agravada ou burla fiscal</strong>: multa penal de 25 000 € a dez vezes o montante do IVA, prisão de um mês a cinco anos, privação dos direitos cívicos de 5 a 10 anos (art. 80 LIVA)</li>
</ul>

<h2>Preparar a inspeção em 5 etapas</h2>

<ol>
    <li><strong>Preparar o FAIA</strong> para o período pedido, se estiver abrangido</li>
    <li><strong>Exportar todas as faturas emitidas</strong>, idealmente em PDF/A</li>
    <li><strong>Verificar a coerência</strong> com as declarações de IVA entregues</li>
    <li><strong>Constituir um dossiê por mês</strong>: faturas, extratos bancários, declaração correspondente</li>
    <li><strong>Avisar o seu contabilista</strong> e pedir-lhe que esteja presente na inspeção</li>
</ol>

<h2>Contestar as conclusões</h2>

<p>Se não concordar, o procedimento está definido:</p>

<ol>
    <li><strong>Reclamação escrita e fundamentada</strong> junto da administração, no prazo de <strong>três meses</strong> a contar da notificação</li>
    <li>Em caso de indeferimento, <strong>o diretor da administração é chamado a decidir</strong> e a sua decisão substitui a anterior</li>
    <li><strong>Recurso por citação</strong> perante o <strong>tribunal de comarca do Luxemburgo, em matéria cível</strong> — o IVA compete à jurisdição judicial, e não à administrativa como os impostos diretos. A citação deve ser notificada no prazo de <strong>três meses</strong> a contar da decisão do diretor, <strong>sob pena de caducidade</strong></li>
</ol>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">O silêncio da administração não o bloqueia</p>
    <p>Se não houver decisão nos <strong>seis meses</strong> seguintes à sua reclamação, pode considerá-la indeferida e interpor recurso diretamente. Nesse caso, o prazo de três meses não corre — a espera não o faz caducar.</p>
</div>

<h2>Como o faktur.lu o prepara</h2>

<ul>
    <li>Numeração sequencial automática conforme ao <strong>artigo 63 LIVA</strong></li>
    <li>Menções obrigatórias geradas em cada fatura</li>
    <li>Validação VIES para as operações B2B intra-UE</li>
    <li>Exportação <strong>FAIA 2.01</strong> em qualquer período</li>
    <li>Arquivo <strong>PDF/A</strong> com impressão de integridade</li>
    <li>Portal do contabilista em modo de leitura, sem trocas de e-mail</li>
</ul>

<h2>Perguntas frequentes</h2>

<h3>Quanto tempo dura uma inspeção?</h3>
<p>Uma inspeção documental: duas a quatro semanas, consoante a rapidez com que enviar os documentos. Uma inspeção no local: geralmente um a três dias na empresa, depois várias semanas de análise na AED.</p>

<h3>Posso recusar uma inspeção?</h3>
<p>Não. A recusa é sancionada e presume má-fé. Pode, contudo, pedir adiamento por motivo legítimo e exigir a presença do seu contabilista.</p>

<h3>Tenho mesmo de arquivar dez anos se a prescrição é de cinco?</h3>
<p>Sim. São duas obrigações independentes: a conservação de dez anos decorre do art. 65 LIVA e do art. 16 do Código Comercial, não do prazo de prescrição.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Procedimentos, sanções e prazos podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Lei do IVA coordenada - artigos 63, 77, 80, 81</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">Sanções de IVA e vias de recurso</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED - FAQ FAIA (âmbito de aplicação)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Obrigações contabilísticas</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 31 de julho de 2026.</em></p>

<h2>Para ir mais longe</h2>
<ul>
    <li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide">FAIA Luxemburgo: tudo sobre o ficheiro de auditoria informatizado</a></li>
    <li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg">Menções obrigatórias numa fatura luxemburguesa</a></li>
    <li><a href="/pt/blog/archivage-factures-luxembourg-duree-legale-format">Arquivo das faturas: prazo legal e formato</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Prepare já a sua próxima inspeção</h3>
    <p class="text-primary-800 mb-4">Numeração automática, FAIA 2.01 num clique, menções conformes, arquivo PDF/A. A inspeção torna-se uma formalidade.</p>
    <a href="/pt/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Começar gratuitamente</a>
</div>
PTBODY,

        "delais-paiement-luxembourg-cadre-legal-2026" => <<<'PTBODY'
<p class="lead">No Luxemburgo, os prazos de pagamento são regulados por lei. Quer fature a uma empresa, a uma administração ou a um particular, eis as regras que protegem a sua tesouraria.</p>

<h2>Prazos legais de pagamento</h2>

<h3>Transações B2B (entre empresas)</h3>

<p>A <strong>lei alterada de 18 de abril de 2004</strong> relativa aos prazos de pagamento e aos juros de mora fixa as seguintes regras:</p>

<ul>
    <li><strong>Prazo por defeito</strong>: 30 dias a contar da receção da fatura</li>
    <li><strong>Prazo máximo contratual</strong>: 60 dias</li>
    <li>As partes podem acordar um prazo <strong>superior a 60 dias</strong> apenas se tal não constituir um <strong>abuso manifesto</strong> em relação ao credor</li>
</ul>

<p>Esta lei foi <strong>alterada pela lei de 29 de março de 2013</strong>, que transpõe a diretiva 2011/7/UE. Foi essa revisão que elevou o agravamento para 8 pontos e criou a indemnização fixa de 40 EUR: os artigos que citam apenas o texto de 2004 descrevem um regime ultrapassado.</p>

<h3>Transações B2G (com o setor público)</h3>

<p>Os prazos são mais estritos para as administrações:</p>

<ul>
    <li><strong>Prazo máximo</strong>: 30 dias a contar da receção da fatura</li>
    <li>Este prazo pode ser <strong>alargado até 60 dias no máximo</strong> se estiver expressamente previsto no contrato e for objetivamente justificado pela natureza deste</li>
    <li>O procedimento de aceitação ou verificação não pode exceder <strong>30 dias</strong></li>
</ul>

<h2>A partir de quando corre o prazo?</h2>

<p>O prazo de pagamento começa a correr a partir de:</p>

<ul>
    <li>A <strong>data de receção da fatura</strong> pelo devedor</li>
    <li>Ou a <strong>data de receção dos bens/serviços</strong>, se a data da fatura for incerta ou se esta tiver sido enviada antes</li>
    <li>Ou a <strong>data de aceitação ou verificação</strong>, quando tal procedimento esteja previsto no contrato ou na lei</li>
</ul>

<p><strong>Conselho:</strong> indique sempre claramente a <strong>data de vencimento</strong> na sua fatura. Com o faktur.lu, é calculada automaticamente (30 dias por defeito, personalizável).</p>

<h2>Juros de mora</h2>

<p>Em caso de atraso, os juros são devidos <strong>automaticamente</strong>, sem que seja necessário enviar uma interpelação. A taxa não é, porém, <strong>a mesma consoante a qualidade do seu cliente</strong> — é a confusão mais frequente.</p>

<h3>O seu cliente é um profissional</h3>

<ul>
    <li><strong>Fórmula</strong>: taxa de referência do BCE publicada no Mémorial B no início de cada semestre, <strong>acrescida de 8 pontos percentuais</strong></li>
    <li><strong>1.º semestre de 2026</strong>: <strong>10,15 %</strong> (2,15 % + 8)</li>
    <li>A taxa é <strong>revista todos os semestres</strong>: verifique a que está em vigor antes de quantificar uma reclamação</li>
    <li>Os juros correm a partir do dia seguinte à data de vencimento</li>
</ul>

<h3>O seu cliente é um particular</h3>

<p>O regime das transações comerciais não se aplica. Vale a <strong>taxa de juro legal</strong>, fixada anualmente: <strong>3,75 % para 2026</strong>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A diferença é importante</p>
    <p>10,15 % contra 3,75 %: reclamar a taxa comercial a um consumidor é pedir quase o triplo do devido. A reclamação torna-se contestável e a sua credibilidade sofre precisamente no momento em que precisa de ser levado a sério.</p>
</div>

<h2>Indemnização fixa de cobrança</h2>

<p>Para além dos juros de mora, o credor tem direito a uma <strong>indemnização fixa de 40 EUR</strong> a título de custos de cobrança, devida <strong>por fatura não paga</strong>, automaticamente e sem justificação de despesas.</p>

<p>Se os custos reais de cobrança excederem 40 EUR, o credor pode reclamar o montante efetivo mediante apresentação de comprovativos.</p>

<p>Esta indemnização pertence igualmente ao regime das transações comerciais: não se reclama a um cliente particular.</p>

<h2>Boas práticas nas suas faturas</h2>

<p>Para se proteger em caso de litígio, mencione em cada fatura:</p>

<ul>
    <li>A <strong>data de vencimento</strong> precisa (não apenas «30 dias»)</li>
    <li>As <strong>modalidades de pagamento</strong> (transferência, com IBAN)</li>
    <li>A menção dos <strong>juros de mora</strong> aplicáveis</li>
    <li>A menção da <strong>indemnização fixa de 40 EUR</strong> para os seus clientes profissionais</li>
</ul>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu – Prazos de pagamento e juros de mora</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministério da Justiça – Taxa de juro legal (atualizada a cada semestre)</a></li>
    <li><a href="https://data.legilux.public.lu/filestore/eli/etat/leg/loi/2004/04/18/n8/jo/fr/html/eli-etat-leg-loi-2004-04-18-n8-jo-fr-html.html" target="_blank" rel="noopener">Legilux – Lei de 18 de abril de 2004 (texto original)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/relancer-client-impaye-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Cobrar um cliente em atraso →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Menções obrigatórias →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Faça a gestão dos seus prazos com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">O faktur.lu calcula automaticamente as datas de vencimento, deteta os atrasos e envia lembretes. Mantenha o controlo da sua tesouraria.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os semestres</p>
    <p>A taxa aplicável às transações comerciais muda duas vezes por ano. Esta página é atualizada regularmente, mas antes de quantificar uma reclamação, verifique a taxa do semestre em curso junto do <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministério da Justiça</a> e, para a sua situação pessoal, consulte o seu contabilista.</p>
</div>
PTBODY,

        "factur-x-zugferd-facturation-electronique-europeenne" => <<<'PTBODY'
<p class="lead">O <strong>Factur-X</strong> (chamado <strong>ZUGFeRD</strong> na Alemanha) é o padrão de faturação eletrónica adotado pela França e pela Alemanha, em fase de adoção em toda a União Europeia. Eis tudo o que precisa de saber em 2026.</p>

<h2>O que é o Factur-X?</h2>

<p>O Factur-X é um formato de fatura <strong>«híbrido»</strong> que combina dois elementos num único ficheiro PDF/A-3:</p>

<ul>
    <li>A <strong>parte visual</strong>: o PDF legível que vê no ecrã e pode imprimir</li>
    <li>A <strong>parte estruturada</strong>: um ficheiro XML integrado no PDF, com os dados da fatura num formato normalizado</li>
</ul>

<p>Este duplo formato permite simultaneamente que os <strong>humanos</strong> leiam a fatura e que os <strong>programas</strong> a integrem automaticamente na contabilidade.</p>

<h2>Factur-X vs ZUGFeRD: qual a diferença?</h2>

<p>Tecnicamente, nenhuma. Trata-se do <strong>mesmo padrão</strong>:</p>

<ul>
    <li><strong>Factur-X</strong> é o nome utilizado em França e nos países francófonos (promovido pela <a href="https://fnfe-mpe.org/factur-x/" target="_blank" rel="noopener">FNFE-MPE</a>)</li>
    <li><strong>ZUGFeRD</strong> é o nome utilizado na Alemanha (promovido pelo <a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD</a>, Forum elektronische Rechnung Deutschland)</li>
    <li>Ambos assentam na norma europeia <strong>EN 16931</strong></li>
</ul>

<h2>Os perfis Factur-X</h2>

<p>O Factur-X propõe vários níveis de detalhe:</p>

<ul>
    <li><strong>Minimum</strong>: informações de base (emissor, destinatário, montante total)</li>
    <li><strong>Basic WL</strong>: sem linhas de detalhe, mas com IVA repartido</li>
    <li><strong>Basic</strong>: com as linhas de fatura detalhadas</li>
    <li><strong>EN 16931</strong> (anteriormente «Comfort»): perfil completo conforme à norma europeia – <strong>recomendado</strong></li>
    <li><strong>Extended</strong>: dados suplementares para necessidades específicas</li>
</ul>

<p>O faktur.lu gera faturas Factur-X no perfil <strong>EN 16931</strong>, o mais amplamente aceite.</p>

<h2>Porque é que o Factur-X é importante</h2>

<h3>Para a sua empresa</h3>
<ul>
    <li><strong>Processamento automatizado</strong>: os seus clientes importam a fatura sem reintrodução</li>
    <li><strong>Menos erros</strong>: sem cópia manual de montantes e números</li>
    <li><strong>Pagamento mais rápido</strong>: uma fatura processada automaticamente percorre o circuito de aprovação mais depressa</li>
    <li><strong>Um único documento</strong>: sem envio separado de PDF e ficheiro de dados</li>
</ul>

<h3>Para a União Europeia</h3>
<ul>
    <li>Redução do desvio de IVA graças a dados estruturados e verificáveis</li>
    <li>Harmonização dos formatos entre Estados-Membros em torno da EN 16931</li>
    <li>Base para a declaração digital prevista no pacote ViDA</li>
</ul>

<h2>É obrigatório no Luxemburgo?</h2>

<p>Em 2026, o Factur-X <strong>ainda não</strong> é obrigatório para o B2B doméstico no Luxemburgo. No entanto:</p>

<ul>
    <li>A <strong>França</strong> torna a faturação eletrónica obrigatória: receção para todas as empresas a 1 de setembro de 2026, emissão para as maiores empresas na mesma data, e para microempresas e PME a 1 de setembro de 2027</li>
    <li>A <strong>Alemanha</strong> exige a receção de faturas eletrónicas B2B desde 1 de janeiro de 2025; a obrigação de emissão entra em vigor de forma faseada até 2028</li>
    <li>A diretiva <strong>ViDA</strong> da UE prevê a obrigação para o B2B intra-UE a 1 de julho de 2030</li>
    <li>O <strong>setor público</strong> luxemburguês já utiliza o Peppol, obrigatório desde 2022-2023</li>
</ul>

<p>Antecipar compensa: adotar o Factur-X agora deixa-o preparado para as obrigações futuras e facilita a vida aos clientes que já usam o formato — em especial os franceses a partir de setembro de 2026.</p>

<h2>Gerar uma fatura Factur-X com o faktur.lu</h2>

<p>A geração Factur-X está incluída no <strong>plano Pro</strong>. Funciona a pedido, sobre uma fatura <strong>finalizada</strong>:</p>

<ol>
    <li>Crie e <strong>finalize</strong> a sua fatura — um rascunho não pode ser convertido</li>
    <li>Descarregue o ficheiro <strong>Factur-X</strong>: um PDF/A-3 com o XML integrado</li>
    <li>Ou obtenha o <strong>XML isolado</strong>, se o seu cliente só quiser os dados estruturados</li>
    <li>Envie-o ao seu cliente, que o importa na contabilidade sem reintrodução</li>
</ol>

<p>O perfil utilizado é o <strong>EN 16931</strong> por defeito, o mais amplamente aceite.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Uma precisão que evita uma surpresa desagradável</p>
    <p>O PDF normal que envia habitualmente <strong>não contém</strong> o XML Factur-X: é um ficheiro distinto, a gerar explicitamente. Enquanto os seus clientes não o pedirem, nada o obriga a mudar de hábitos — mas no dia em que um cliente francês o pedir, está a um clique de distância.</p>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os calendários do ViDA, da França e da Alemanha evoluem. Esta página é atualizada regularmente, mas consulte as fontes oficiais abaixo para as datas exatas aplicáveis ao seu caso.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Comissão Europeia - VAT in the Digital Age (ViDA)</a></li>
    <li><a href="https://www.economie.gouv.fr/tout-savoir-sur-la-facturation-electronique-pour-les-entreprises" target="_blank" rel="noopener">França - economie.gouv.fr - Faturação eletrónica</a></li>
    <li><a href="https://fnfe-mpe.org/factur-x/" target="_blank" rel="noopener">FNFE-MPE - Factur-X</a></li>
    <li><a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD - ZUGFeRD</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 31 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/peppol-b2g-luxembourg-guide-complet-2026" class="text-primary-500 hover:text-primary-600 text-sm">Peppol B2G Luxemburgo →</a></li><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Escolher software de faturação →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Gerar faturas Factur-X</h3>
    <p class="text-primary-800 mb-4">O plano Pro gera o ficheiro Factur-X (PDF/A-3 com XML integrado) das suas faturas finalizadas, no perfil EN 16931. Antecipe-se às obrigações europeias que aí vêm.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
PTBODY,

        "facturer-etranger-depuis-luxembourg" => <<<'PTBODY'
<p class="lead">Está sediado no Luxemburgo e fatura clientes no estrangeiro? As regras de IVA variam consideravelmente consoante a zona geográfica e o tipo de cliente. Eis um guia claro para cada situação, com as menções e bases legais corretas (atualizado 2026).</p>

<h2>Caso 1: Cliente empresa na UE (B2B intracomunitário)</h2>

<p>É o caso mais frequente para freelancers e PME luxemburguesas. Exemplo: um consultor luxemburguês fatura uma sociedade alemã.</p>

<h3>Regras a aplicar</h3>
<ul>
    <li>Fatura <strong>sem IVA (0 %)</strong> - lugar de tributação no adquirente (<a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">art. 17 LIVA, transposição do art. 44.º da Diretiva 2006/112/CE</a>)</li>
    <li>O cliente declara o IVA no seu país (<strong>autoliquidação / reverse charge</strong>) - o art. 196.º da diretiva designa-o como devedor</li>
    <li>Deve verificar o número de IVA do cliente no <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a></li>
    <li>Menção obrigatória (art. 226.º §11bis da diretiva): <em>«Autoliquidação - Artigo 196.º da Diretiva 2006/112/CE»</em></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Menção artigo 196.º, não 44.º</p>
    <p>Muitos modelos cometem o erro de mencionar «artigo 44.º da Diretiva 2006/112/CE». O artigo 44.º define o <strong>lugar de tributação</strong>. A menção obrigatória a apor remete para o <strong>artigo 196.º</strong> (que designa o adquirente como devedor do IVA). Prefira a menção do artigo 196.º.</p>
</div>

<h3>Documentos necessários</h3>
<ul>
    <li>O seu número de IVA luxemburguês na fatura</li>
    <li>O número de IVA do cliente (verificado no VIES, prova a conservar)</li>
    <li>Declaração no <strong>mapa recapitulativo</strong> — a sua periodicidade é determinada pela AED consoante a sua situação</li>
</ul>

<h2>Caso 2: Cliente particular na UE (B2C)</h2>

<p>Vende a um particular noutro país da UE. As regras dependem do tipo de prestação:</p>

<h3>Serviços clássicos (consultoria, design, etc.)</h3>
<ul>
    <li>Aplica o <strong>IVA luxemburguês (17 %)</strong> por defeito</li>
    <li>Não há autoliquidação para particulares</li>
    <li><strong>Atenção:</strong> certos serviços B2C têm regras específicas (imóveis, transporte, eventos culturais/desportivos no local…) e são tributados onde são executados. Se for o seu caso, confirme com o seu contabilista.</li>
</ul>

<h3>Serviços eletrónicos (SaaS, formações em linha, etc.)</h3>
<ul>
    <li>Aplica o <strong>IVA do país do cliente</strong></li>
    <li>Através do regime <strong>OSS (One-Stop Shop)</strong>: uma única declaração para todos os países da UE</li>
    <li>Limiar: <strong>10 000 €/ano</strong> de vendas B2C na UE (soma de vendas à distância de bens e serviços TBE). Abaixo disso, pode aplicar o IVA luxemburguês.</li>
</ul>

<h2>Caso 3: Cliente fora da UE (exportação)</h2>

<p>Fatura um cliente na Suíça, nos Estados Unidos, no Reino Unido ou em qualquer outro país fora da UE.</p>

<h3>Serviços</h3>
<ul>
    <li>Fatura <strong>sem IVA (0 %)</strong> - serviço localizado fora da UE (art. 17 LIVA)</li>
    <li>Menção recomendada: <em>«IVA não aplicável - serviço localizado fora da UE»</em></li>
    <li>Sem mapa recapitulativo (reservado às trocas intra-UE)</li>
</ul>

<h3>Bens (exportação fora da UE)</h3>
<ul>
    <li>Fatura <strong>sem IVA</strong> (exportação isenta, art. 43 §1 a) LIVA)</li>
    <li>Deve conservar a <strong>prova de exportação</strong> (documento aduaneiro)</li>
    <li>Menção: <em>«Isenção de IVA - Artigo 146.º da Diretiva 2006/112/CE»</em></li>
</ul>

<h3>O caso particular da Irlanda do Norte</h3>

<p>Colocar o Reino Unido em bloco na categoria «fora da UE» é correto para os serviços, mas <strong>errado para os bens</strong>. Ao abrigo do protocolo Irlanda / Irlanda do Norte, a legislação de IVA da União continua a aplicar-se aos <strong>bens</strong> com destino e proveniência da Irlanda do Norte.</p>

<ul>
    <li><strong>Bens para a Irlanda do Norte</strong>: entrega intracomunitária, e não exportação. O cliente tem um número de IVA com o prefixo <strong>«XI»</strong>, a validar no VIES, e a operação consta do seu mapa recapitulativo</li>
    <li><strong>Serviços para a Irlanda do Norte</strong>: regime de país terceiro, como para o resto do Reino Unido</li>
    <li>Um número de IVA válido com o prefixo correto é uma <strong>condição substantiva</strong> da isenção: um número «GB» não permite tratar a operação como entrega intracomunitária</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Um erro que custa nos dois sentidos</p>
    <p>Tratar uma venda de bens para a Irlanda do Norte como exportação leva-o a procurar uma prova aduaneira que nunca existirá, e a omitir o mapa recapitulativo. Inversamente, tratar uma prestação de serviços como intracomunitária leva-o a declarar uma operação que ali não pertence. O critério é a <strong>natureza da operação</strong>, não o país.</p>
</div>

<h2>Caso especial: a Suíça</h2>

<p>A Suíça não pertence à UE. Muitos freelancers luxemburgueses faturam clientes suíços. As regras:</p>

<ul>
    <li><strong>Serviços B2B</strong>: fature <strong>sem IVA</strong>, o cliente suíço declara o imposto através do mecanismo do <a href="https://www.estv.admin.ch/" target="_blank" rel="noopener">imposto sobre as aquisições (ESTV)</a></li>
    <li><strong>Serviços B2C</strong>: consoante o tipo de serviço, o IVA luxemburguês pode aplicar-se (nomeadamente serviços eletrónicos)</li>
    <li>Fature em <strong>EUR ou CHF</strong> conforme o acordado com o cliente</li>
    <li>Sem mapa recapitulativo (reservado às trocas intra-UE)</li>
</ul>

<h2>Tabela recapitulativa</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Cenário</th>
            <th class="border border-gray-300 px-4 py-2 text-left">IVA</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Menção</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Luxemburgo</td><td class="border border-gray-300 px-4 py-2">17 %</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B UE (intra-UE)</td><td class="border border-gray-300 px-4 py-2">0 % (autoliquidação)</td><td class="border border-gray-300 px-4 py-2">Art. 196.º Diretiva 2006/112/CE</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (serviços clássicos)</td><td class="border border-gray-300 px-4 py-2">17 % LU (por defeito)</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (TBE / serviços eletrónicos) - &gt; 10 k€</td><td class="border border-gray-300 px-4 py-2">IVA do país do cliente</td><td class="border border-gray-300 px-4 py-2">Regime OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B fora da UE</td><td class="border border-gray-300 px-4 py-2">0 % (não tributável no LU)</td><td class="border border-gray-300 px-4 py-2">«IVA não aplicável - serviço localizado fora da UE»</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Exportação de bens fora da UE</td><td class="border border-gray-300 px-4 py-2">0 % (isento)</td><td class="border border-gray-300 px-4 py-2">Art. 146.º Diretiva 2006/112/CE</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>Bens para a Irlanda do Norte</strong></td><td class="border border-gray-300 px-4 py-2">0 % (entrega intracomunitária)</td><td class="border border-gray-300 px-4 py-2">Art. 138.º Diretiva - número do cliente com prefixo «XI»</td></tr>
    </tbody>
</table>

<h2>Multimoeda</h2>

<p>Se faturar em moeda estrangeira, o montante do IVA deve ser <strong>convertido em euros</strong> para a sua declaração luxemburguesa. Dois princípios valem:</p>

<ul>
    <li>Adote um <strong>método de conversão constante</strong> e aplique-o a todas as operações do exercício</li>
    <li>Garanta a <strong>coerência entre a fatura e a contabilidade</strong>: é a mesma taxa que deve constar de ambas</li>
</ul>

<p>A escolha da taxa de referência depende da sua situação: peça ao seu contabilista que a valide de uma vez por todas, em vez de a improvisar fatura a fatura.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Não confunda com a taxa aduaneira</p>
    <p>A <a href="https://douanes.public.lu/fr/commerce-international/taux-change.html" target="_blank" rel="noopener">Administração das alfândegas publica as suas próprias taxas de câmbio</a>, que servem para estabelecer o <strong>valor aduaneiro</strong> das mercadorias. É uma taxa distinta da utilizada para converter a base tributável do IVA. Se exportar bens, lidará com ambas.</p>
</div>

<h2>O que muda com o ViDA (2027-2030)</h2>

<p>O pacote europeu <strong>ViDA</strong> («IVA na era digital») foi adotado. Ao contrário do que se lê frequentemente, não começa em 2030: o calendário é <strong>faseado</strong> e os primeiros prazos chegam bem antes.</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Prazo</th>
            <th class="border border-gray-300 px-4 py-2 text-left">O que entra em vigor</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Janeiro de 2027</td><td class="border border-gray-300 px-4 py-2">Primeira extensão do balcão único OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Julho de 2028</td><td class="border border-gray-300 px-4 py-2">Autoliquidação obrigatória em certos casos, regras aplicáveis às plataformas, nova extensão do OSS às vendas B2C domésticas</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">1 de julho de 2030</td><td class="border border-gray-300 px-4 py-2">Faturação eletrónica e declaração digital obrigatórias para as operações intra-UE B2B</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Janeiro de 2035</td><td class="border border-gray-300 px-4 py-2">Harmonização dos sistemas nacionais existentes com a norma europeia</td></tr>
    </tbody>
</table>

<p>Se fatura regularmente clientes na UE, são <strong>2027 e 2028</strong> que o afetam primeiro, não 2030.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>As regras de IVA intra-UE e internacionais evoluem, e o calendário ViDA pode ainda ser precisado. Esta página é atualizada regularmente, mas para a sua situação, consulte o seu contabilista ou a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED - Determinação do lugar da prestação</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/value-added-tax/intracommunity-transactions.html" target="_blank" rel="noopener">Logistics.lu - Transações intracomunitárias</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/PT/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Diretiva 2006/112/CE - artigos 44.º, 138.º, 146.º, 196.º</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - Validação de IVA intra-UE</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/place-taxation_en" target="_blank" rel="noopener">Comissão Europeia - Lugar de tributação</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/article-17-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Artigo 17 LIVA: autoliquidação B2B intra-UE →</a></li><li><a href="/pt/blog/tva-intracommunautaire-guide-entreprises-luxembourgeoises" class="text-primary-500 hover:text-primary-600 text-sm">IVA intracomunitário - guia completo →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Menções obrigatórias numa fatura luxemburguesa →</a></li><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Comparativo: escolher software de faturação →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fature para o estrangeiro em plena conformidade</h3>
    <p class="text-primary-800 mb-4">O faktur.lu deteta automaticamente o cenário de IVA consoante o cliente (país, B2B/B2C) e aplica a menção correta. Validação VIES integrada.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
PTBODY,

        "faia-luxembourg-fichier-audit-informatise-guide" => <<<'PTBODY'
<p class="lead">O FAIA (ficheiro de auditoria informatizado) é um ficheiro que a AED pode exigir durante uma inspeção fiscal. Ao contrário de uma ideia difundida, não diz respeito a todas as empresas luxemburguesas: quatro condições cumulativas determinam quem tem de ser capaz de o produzir.</p>

<h2>O que é o FAIA?</h2>

<p>O <strong>FAIA (Fichier d'Audit Informatisé)</strong>, também chamado <strong>SAF-T Luxemburgo</strong>, é um ficheiro em formato XML normalizado que contém a totalidade dos dados contabilísticos e fiscais de uma empresa para um dado período.</p>

<p>A sua base legal é a <strong>lei de 19 de dezembro de 2008</strong> (Mémorial A-206 de 24 de dezembro de 2008), que alterou o <strong>artigo 70.º, parágrafo 3, da lei do IVA</strong>. Este texto prevê que os livros e documentos existentes sob forma eletrónica devem, a pedido da administração, ser comunicados «numa forma legível e diretamente inteligível» ou segundo quaisquer outras modalidades técnicas que a administração determine. O FAIA é a modalidade escolhida pela AED.</p>

<h2>Quem deve produzir um ficheiro FAIA?</h2>

<p>É o ponto mais frequentemente deturpado. Segundo as <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">FAQ oficiais da AED</a>, a obrigação pressupõe que <strong>quatro condições estejam reunidas ao mesmo tempo</strong>.</p>

<h3>As quatro condições cumulativas</h3>

<ol>
    <li>Estar <strong>sujeito ao plano de contas normalizado (PCN)</strong></li>
    <li><strong>Não beneficiar de um regime simplificado</strong></li>
    <li>Realizar um <strong>volume de negócios anual superior a 112 000 €</strong></li>
    <li>Ultrapassar um volume de cerca de <strong>500 transações contabilísticas</strong> por ano</li>
</ol>

<p>Se faltar apenas uma destas condições, o FAIA não lhe é aplicável. A própria AED o formula sem rodeios nas suas FAQ:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>«Realizo um volume de negócios de 1.000.000 € e tenho apenas 400 transações na minha contabilidade. Sou obrigado a fornecer um ficheiro FAIA? — <strong>Não.</strong> Embora o seu volume de negócios ultrapasse os 112.000 €, o seu volume de transações mantém-se em limites em que um controlo manual é mais racional.»</p>
</blockquote>

<h3>O que é realmente uma «transação»</h3>

<p>Atenção à contagem: uma transação <strong>não é uma fatura</strong>. A AED define-a como uma <strong>cadeia inteira de lançamentos</strong>. Uma compra, por exemplo, decompõe-se em quatro lançamentos ligados — conta de compra, IVA a montante, conta de fornecedor, pagamento — que formam em conjunto <strong>uma única</strong> transação.</p>

<p>Quem conta as suas 600 faturas e conclui que ultrapassa o limiar está, portanto, provavelmente a medir a coisa errada.</p>

<h3>Se não estiver sujeito ao PCN</h3>

<p>Escapa à obrigação FAIA propriamente dita, mesmo com um volume de negócios elevado e mais de 500 transações. Mas o artigo 70.º continua a aplicar-se: a AED pode exigir que exporte os seus dados eletrónicos <strong>num formato delimitado e estruturado</strong>. Estar fora do FAIA não dispensa de ser capaz de extrair a contabilidade corretamente.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Importante</p>
    <p class="text-amber-700">O FAIA nunca se transmite espontaneamente e, em particular, <strong>não com a sua declaração de IVA</strong>. É produzido <strong>unicamente a pedido</strong> de um agente da AED encarregado da inspeção da sua empresa.</p>
</div>

<h2>O que contém o ficheiro FAIA?</h2>

<p>O ficheiro FAIA está estruturado em várias secções:</p>

<h3>1. Informações gerais (Header)</h3>

<ul>
    <li>Identificação da empresa (nome, endereço, número de IVA)</li>
    <li>Período abrangido pelo ficheiro</li>
    <li>Informações sobre o software utilizado</li>
    <li>Data e hora de geração</li>
</ul>

<h3>2. Plano de contas (GeneralLedger)</h3>

<ul>
    <li>Lista de todas as contas contabilísticas utilizadas</li>
    <li>Hierarquia das contas</li>
    <li>Saldos de abertura e de encerramento</li>
</ul>

<h3>3. Clientes e fornecedores (MasterFiles)</h3>

<ul>
    <li>Ficheiro de clientes com dados completos</li>
    <li>Ficheiro de fornecedores</li>
    <li>Números de IVA intracomunitários</li>
</ul>

<h3>4. Lançamentos contabilísticos (GeneralLedgerEntries)</h3>

<ul>
    <li>Todos os lançamentos do período, incluindo os sem ligação direta ao IVA — a exportação deve abranger a totalidade da contabilidade</li>
    <li>Diários contabilísticos</li>
    <li>Documentos de suporte referenciados</li>
</ul>

<h3>5. Faturas (SourceDocuments)</h3>

<ul>
    <li>Faturas de venda emitidas</li>
    <li>Faturas de compra recebidas</li>
    <li>Notas de crédito</li>
    <li>Detalhe linha a linha com IVA</li>
</ul>

<p>Se o seu sistema de faturação estiver <strong>integrado na contabilidade</strong>, os documentos de origem devem ser entregues sistematicamente. Se não estiver, o agente da AED pode pedir documentos de origem específicos.</p>

<h2>Formato técnico do FAIA</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Formato</td>
            <td class="border border-gray-300 px-4 py-2">XML (Extensible Markup Language)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Codificação</td>
            <td class="border border-gray-300 px-4 py-2">UTF-8</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Esquema XSD</td>
            <td class="border border-gray-300 px-4 py-2">FAIA_v2.01.xsd, última atualização publicada pela AED em julho de 2020. Coexistem três esquemas: <em>full</em>, <em>reduced version A</em> e <em>reduced version B</em>, consoante o regime contabilístico</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Período</td>
            <td class="border border-gray-300 px-4 py-2">Um exercício inteiro, alinhado pelo ano civil. Os exercícios truncados são recusados e um ficheiro só pode cobrir um período: uma inspeção a três anos exige três ficheiros</td>
        </tr>
    </tbody>
</table>

<h2>Como gerar um ficheiro FAIA conforme?</h2>

<h3>Opção 1: software de faturação compatível</h3>

<p>É a solução mais simples. Um software como o <strong>faktur.lu</strong> gera automaticamente um ficheiro FAIA conforme a partir dos seus dados de faturação.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ Exportação FAIA num clique com o faktur.lu</p>
    <p class="text-green-700">O nosso software gera um ficheiro FAIA validado segundo o esquema XSD oficial, pronto a ser transmitido à AED — quer esteja obrigado hoje, quer ultrapasse os limiares amanhã.</p>
</div>

<h3>Opção 2: software de contabilidade</h3>

<p>Os programas de contabilidade profissionais (Sage, BOB, etc.) propõem geralmente um módulo de exportação FAIA.</p>

<h3>Opção 3: desenvolvimento à medida</h3>

<p>Para grandes empresas com sistemas proprietários, pode ser necessário um desenvolvimento específico para extrair e formatar os dados segundo o esquema FAIA.</p>

<h2>Validação do ficheiro FAIA</h2>

<p>Antes de transmitir o ficheiro, valide-o:</p>

<ol>
    <li><strong>Validação XSD</strong>: verificar que o ficheiro respeita o esquema XML oficial</li>
    <li><strong>Controlo dos totais</strong>: assegurar que as somas são coerentes</li>
    <li><strong>Verificação das referências</strong>: todos os identificadores (clientes, contas) devem estar presentes</li>
</ol>

<p>A AED é explícita neste ponto: <strong>não é disponibilizada qualquer ferramenta de validação</strong> e «apenas o esquema publicado no sítio da AED pode servir de mecanismo de controlo». Pode, portanto, utilizar qualquer validador XML de terceiros (por exemplo o <a href="/pt/validateur-faia">validador faktur.lu</a>) para verificar a conformidade antes da transmissão.</p>

<h2>Prazos, transmissão e sanções</h2>

<h3>Prazo de produção</h3>

<p>A AED não publica qualquer prazo legal fixo. Quando um ficheiro FAIA é pedido no âmbito de uma inspeção, o prazo é fixado <strong>caso a caso pelo inspetor</strong>, consoante a complexidade do pedido.</p>

<h3>Suporte de transmissão</h3>

<p>A AED mostra-se flexível: qualquer suporte eletrónico corrente disponível no mercado é aceite — pen USB, disco externo, CD-R ou DVD-R, e-mail.</p>

<h3>Sanções em caso de não conformidade</h3>

<p>Para as empresas efetivamente abrangidas pela obrigação, a recusa ou incapacidade de fornecer os dados pode acarretar:</p>

<ul>
    <li><strong>Coimas administrativas</strong></li>
    <li>Uma <strong>tributação oficiosa</strong> pela administração</li>
    <li>A <strong>rejeição da contabilidade</strong> como prova</li>
</ul>

<h2>Boas práticas</h2>

<ol>
    <li><strong>Verifique primeiro se está abrangido</strong> — as quatro condições devem estar reunidas</li>
    <li><strong>Teste regularmente</strong> a sua exportação FAIA, e não apenas durante uma inspeção</li>
    <li><strong>Arquive</strong> os ficheiros FAIA gerados para cada exercício</li>
    <li><strong>Verifique a coerência</strong> entre as suas faturas e os seus lançamentos contabilísticos</li>
    <li><strong>Utilize software certificado</strong> ou testado para a exportação FAIA</li>
</ol>

<h2>Os 4 erros FAIA mais frequentes</h2>

<ol>
    <li><strong>Numeração das faturas não conforme</strong> ao artigo 63 LIVA ponto 3° (falhas na sequência ou duplicados). O ficheiro pode ser rejeitado na validação.</li>
    <li><strong>Campos obrigatórios em falta</strong>: número de IVA do cliente, endereço completo, menção de autoliquidação para o B2B intra-UE.</li>
    <li><strong>Totais incoerentes</strong> entre as secções — por exemplo, uma soma de faturas diferente do total declarado.</li>
    <li><strong>Formato de data incorreto</strong>: o FAIA exige a norma ISO 8601 (AAAA-MM-DD), não DD/MM/AAAA.</li>
</ol>

<h2>Perguntas frequentes</h2>

<h3>É preciso enviar um FAIA todos os anos?</h3>
<p>Não. O FAIA é produzido unicamente a pedido da AED durante uma inspeção, e apenas pelas empresas abrangidas. Nunca se entrega juntamente com a declaração de IVA.</p>

<h3>O que acontece se não o conseguir fornecer estando obrigado?</h3>
<p>O inspetor considera que a sua contabilidade não é mantida segundo os padrões. Arrisca uma coima administrativa de 250 € a 10 000 € por infração (art. 77 LIVA), uma tributação oficiosa e — em caso de recusa de apresentação dos documentos — uma coima que pode atingir 25 000 € por dia de atraso após advertência.</p>

<h3>O FAIA é obrigatório em regime de isenção de IVA?</h3>
<p>Em geral, não. A isenção (art. 57bis LIVA, volume de negócios ≤ 50 000 €) coloca o sujeito passivo abaixo do limiar de 112 000 €, e o regime simplificado é, de qualquer modo, por si só um motivo de exclusão.</p>

<h3>Como saber se o meu ficheiro está conforme antes de uma inspeção?</h3>
<p>Valide-o contra o esquema XSD oficial. O <a href="/pt/validador-faia">validador FAIA do faktur.lu</a> é gratuito e sem inscrição: verifica a conformidade com o esquema 2.01, deteta campos em falta, controla a coerência dos totais e a sequencialidade dos números de fatura, sem armazenar os seus dados.</p>

<h3>O meu contabilista pode gerar o FAIA por mim?</h3>
<p>Sim. O seu contabilista pode produzi-lo a partir da sua própria ferramenta, ou obter o seu através de um portal contabilístico em modo de leitura.</p>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED - Página oficial do FAIA</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED - Esquemas XSD FAIA 2.01</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED - FAQ FAIA (âmbito de aplicação e exclusões)</a></li>
</ul>

<h2>Conclusão</h2>

<p>O FAIA é uma obrigação real, mas dirigida: visa as empresas sujeitas ao plano de contas normalizado, ao regime normal, acima de 112 000 € de volume de negócios e de cerca de 500 transações anuais. Muitos independentes e pequenas estruturas não estão abrangidos.</p>

<p>Se estiver abrangido — ou se o crescimento o levar até lá —, um software de faturação capaz de produzir o ficheiro poupa-lhe a descoberta do problema no dia da inspeção. O faktur.lu integra nativamente a exportação FAIA, validada segundo o esquema oficial da AED.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">inspeção fiscal →</a></li><li><a href="/pt/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">arquivo de faturas →</a></li></ul></div>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os limiares e procedimentos podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
PTBODY,

        "franchise-tva-luxembourg-seuil-obligations-regime-normal" => <<<'PTBODY'
<p class="lead">No Luxemburgo, as pequenas empresas cujo volume de negócios anual sem IVA não excede <strong>50 000 €</strong> podem beneficiar do <strong>regime de isenção de IVA</strong> (artigo 57bis da lei do IVA luxemburguesa). Este limiar passou de 35 000 € para 50 000 € a 1 de janeiro de 2025, ao mesmo tempo que surgiu um <strong>regime transfronteiriço</strong> ainda pouco conhecido. Eis tudo o que é preciso saber em 2026.</p>

<h2>O que é a isenção de IVA?</h2>

<p>A isenção de IVA (<a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">artigo 57bis LIVA</a>) é um <strong>regime especial</strong> que permite às pequenas empresas isentar de IVA os bens e serviços que vendem. Na prática:</p>

<ul>
    <li><strong>Não fatura IVA</strong> aos seus clientes</li>
    <li><strong>Não entrega IVA</strong> ao Estado</li>
    <li>Está dispensado da obrigação de apresentar <strong>declarações de IVA ordinárias</strong></li>
</ul>

<p>Em contrapartida, <strong>não pode recuperar o IVA</strong> pago nas suas compras profissionais.</p>

<h2>Condições de elegibilidade</h2>

<ul>
    <li>Volume de negócios anual sem IVA <strong>igual ou inferior a 50 000 €</strong> no ano civil</li>
    <li><strong>Tolerância de 10 %</strong>: se ultrapassar durante o ano sem exceder <strong>55 000 €</strong>, mantém-se isento até 31 de dezembro</li>
    <li>Sede da atividade económica no Luxemburgo (um simples estabelecimento estável não basta)</li>
    <li>O regime é <strong>opcional</strong>: pode preferir o regime normal</li>
</ul>

<p><strong>Operações excluídas da isenção:</strong> as operações ocasionais referidas no artigo 12.º da Diretiva 2006/112/CE, e as <strong>entregas de meios de transporte novos</strong> para outro Estado-Membro. Estão igualmente excluídos os sujeitos passivos do regime do grupo de IVA, do regime forfetário agrícola e florestal, ou com opções imobiliárias incompatíveis.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Sabia que?</p>
    <p>O limiar subiu de 35 000 € para 50 000 € a 1 de janeiro de 2025, no âmbito da transposição da <a href="https://eur-lex.europa.eu/legal-content/PT/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Diretiva (UE) 2020/285</a>. O teto máximo que um Estado-Membro pode fixar é de 85 000 €; o Luxemburgo não prevê limiares setoriais.</p>
</div>

<h2>Aquilo de que a isenção não o dispensa</h2>

<p>É o ponto mais mal compreendido do regime, e afeta muita gente. Estar isento <strong>não significa estar dispensado de todas as obrigações declarativas</strong>.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Se fatura clientes profissionais na UE</p>
    <p class="text-red-700">A partir do momento em que realiza <strong>prestações de serviços intracomunitárias</strong> — ou se torna devedor de IVA no Luxemburgo ao abrigo do artigo 61 da lei do IVA — deve <strong>obrigatoriamente</strong>:</p>
    <ul class="text-red-700 mt-2">
        <li>entregar uma <strong>declaração anual simplificada</strong> através do portal <strong>eCDF</strong>, <strong>antes de 1 de março</strong> do ano civil seguinte;</li>
        <li>apresentar <strong>mapas recapitulativos</strong> relativos a essas prestações de serviços intracomunitárias.</li>
    </ul>
    <p class="text-red-700 mt-2">Um único cliente profissional na Alemanha, em França ou na Bélgica desencadeia ambas as obrigações.</p>
</div>

<p>Fora desse caso, o sujeito passivo isento comunica o seu volume de negócios anual à repartição de finanças — por correio, por e-mail, ou através do formulário de declaração anual simplificada disponibilizado pela AED.</p>

<p>Por fim, se estiver sob isenção e depois sob o regime normal <strong>no mesmo ano</strong>, o volume de negócios realizado sob isenção indica-se no <strong>campo 481</strong> da declaração de IVA entregue no âmbito do regime normal.</p>

<h2>Menções obrigatórias nas suas faturas</h2>

<p>Mesmo em isenção de IVA, as suas faturas devem conter a <strong>menção exata</strong> seguinte:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    «IVA não aplicável – Artigo 57bis da lei alterada de 12 de fevereiro de 1979»
</blockquote>

<p>Deve igualmente:</p>
<ul>
    <li><strong>Não mencionar IVA</strong> (sem linha de IVA na fatura)</li>
    <li>Não indicar qualquer <strong>taxa de IVA</strong></li>
    <li>Indicar apenas o <strong>montante líquido</strong> (sem distinção sem IVA/com IVA)</li>
</ul>

<p>Boa notícia desde 1 de janeiro de 2025: o sujeito passivo que beneficia da isenção está autorizado a emitir <strong>faturas simplificadas</strong>.</p>

<h2>Vantagens da isenção</h2>

<ul>
    <li><strong>Simplicidade</strong>: sem declaração de IVA ordinária, faturação simplificada autorizada</li>
    <li><strong>Competitividade B2C</strong>: a preço líquido igual, a sua fatura é cerca de <strong>14,5 % mais barata</strong> para um particular do que a de um concorrente no regime normal (17 % de IVA sobre 117 € com IVA). Atenção, porém: essa vantagem é reduzida pelo IVA que não recupera nas suas próprias compras</li>
    <li><strong>Tesouraria</strong>: sem desfasamento entre IVA recebido e IVA entregue</li>
    <li><strong>Menos burocracia</strong>: administração simplificada</li>
</ul>

<h2>Inconvenientes</h2>

<ul>
    <li><strong>Sem dedução do IVA a montante</strong>: paga IVA nas compras sem o recuperar (decisivo se investir)</li>
    <li><strong>Desvantagem B2B</strong>: os clientes empresas não deduzem IVA das suas faturas, pelo que o seu preço lhes sai mais caro</li>
    <li><strong>Imagem</strong>: alguns clientes B2B preferem trabalhar com empresas sujeitas a IVA</li>
    <li><strong>Obrigações residuais</strong>: assim que fatura serviços a profissionais da UE, a declaração anual simplificada e os mapas recapitulativos voltam a ser obrigatórios</li>
    <li><strong>Teto restritivo</strong>: ultrapassado o limiar e a sua tolerância, a passagem ao regime normal impõe-se</li>
</ul>

<h2>O regime de isenção transfronteiriço (desde 2025)</h2>

<p>É a segunda novidade de 1 de janeiro de 2025, bem menos comentada do que a subida do limiar. Até então, a isenção só valia no país de estabelecimento. Agora, uma pequena empresa luxemburguesa pode <strong>beneficiar da isenção noutros Estados-Membros</strong>.</p>

<h3>As condições</h3>

<ul>
    <li>Respeitar o <strong>limiar nacional de cada Estado-Membro</strong> onde pretende a isenção (os limiares variam: consulte o <a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">portal europeu SME VAT rules</a>)</li>
    <li>Realizar um volume de negócios <strong>inferior a 100 000 € em toda a União</strong> — o «limiar da União», que inclui as suas vendas no Luxemburgo</li>
    <li>Ter a <strong>sede da sua atividade económica</strong> no Luxemburgo. Um sujeito passivo com sede num país terceiro não pode beneficiar dele, mesmo com um estabelecimento estável na UE</li>
</ul>

<h3>O número «EX»</h3>

<p>O procedimento passa por uma <strong>notificação prévia</strong> no seu espaço profissional em <strong>MyGuichet.lu</strong>. A AED transmite o pedido aos Estados-Membros em causa. Assim que pelo menos um aceite, recebe um número de identificação <strong>«EX»</strong>, com a forma <strong>LU12345678-EX</strong> — geralmente no prazo de <strong>35 dias úteis</strong>.</p>

<p>A isenção só começa a aplicar-se num Estado-Membro <strong>a partir da data de comunicação ou de confirmação</strong> desse número. Ponto importante: <strong>não é possível qualquer identificação retroativa</strong>. As vendas realizadas antes da obtenção do número não são recuperáveis.</p>

<h3>As suas obrigações declarativas</h3>

<ul>
    <li><strong>Declaração trimestral</strong> à AED do volume de negócios total realizado em <strong>todos</strong> os Estados-Membros — incluindo o Luxemburgo</li>
    <li>Em contrapartida, não tem de se identificar nem entregar declarações de IVA nos Estados-Membros onde não está estabelecido, quanto às operações abrangidas pela isenção</li>
    <li>As operações <strong>não abrangidas</strong> pela isenção nesses Estados (nomeadamente aquisições intracomunitárias) continuam sujeitas às obrigações locais</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Se ultrapassar o limiar da União</p>
    <p>Acima de 100 000 € de volume de negócios na União, perde a isenção nos Estados-Membros onde não está estabelecido — <strong>incluindo no ano civil seguinte</strong>, mesmo que os limiares nacionais aí sejam respeitados. Em contrapartida, mantém a isenção nacional no Luxemburgo enquanto cumprir as condições.</p>
</div>

<h2>Quando passar ao regime normal?</h2>

<h3>Passagem obrigatória (ultrapassagem do limiar)</h3>

<p>A regra depende do <strong>nível de ultrapassagem</strong> do limiar de 50 000 € sem IVA:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situação</th>
            <th class="text-left p-2 bg-slate-100">Efeito</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Ultrapassagem de 10 % no máximo (até 55 000 €)</td><td class="p-2 border-b">Mantém-se isento até 31 de dezembro. Passagem ao regime normal a <strong>1 de janeiro do ano seguinte</strong>.</td></tr>
        <tr><td class="p-2 border-b">Ultrapassagem superior a 10 % (acima de 55 000 €)</td><td class="p-2 border-b">A isenção deixa de se aplicar <strong>a partir do dia seguinte ao da ultrapassagem</strong>.</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">O exemplo oficial da AED</p>
    <p>Um sujeito passivo atinge 51 000 €: ultrapassagem inferior a 10 %, mantém-se isento. A <strong>8 de setembro de 2025</strong>, uma venda de 5 000 € leva o volume de negócios a <strong>56 000 €</strong>. A ultrapassagem excede 10 %: fica excluído do regime de isenção <strong>a partir de 9 de setembro de 2025</strong>, e assim permanece durante todo o ano de 2026.</p>
    <p class="mt-2">Repare na mecânica: é o <strong>dia seguinte</strong>. A fatura que faz ultrapassar o teto é ainda emitida em isenção; é a seguinte que leva IVA.</p>
</div>

<p><strong>Em ambos os casos</strong>, ultrapassar o limiar durante um ano civil exclui-o da isenção durante <strong>todo o ano civil seguinte</strong>, qualquer que seja a percentagem.</p>

<p>Deve então:</p>

<ol>
    <li>Informar a <strong>AED</strong> da mudança de regime através do MyGuichet.lu ou da sua repartição de finanças</li>
    <li>Começar a faturar IVA segundo o calendário acima</li>
    <li>Cumprir a periodicidade declarativa determinada pela AED</li>
</ol>

<h3>Passagem voluntária (opção pelo regime normal)</h3>

<p>Pode optar pelo regime normal <strong>mesmo abaixo do limiar</strong>, mediante pedido à sua repartição de finanças. <strong>A opção produz efeitos no 1.º dia do mês seguinte</strong> e vincula-o por <strong>pelo menos um ano civil</strong>: não é uma ida e volta.</p>

<p>É muitas vezes a escolha certa se:</p>

<ul>
    <li>Os seus clientes são maioritariamente <strong>empresas (B2B)</strong> que deduzem IVA</li>
    <li>Tem <strong>investimentos importantes</strong> (equipamento, viatura profissional) e quer recuperar o IVA a montante</li>
    <li>Está a aproximar-se do limiar e prefere <strong>antecipar</strong> em vez de mudar a tarifação a meio do ano</li>
    <li>Fatura <strong>internacionalmente (B2B intra-UE)</strong> e precisa de um número de IVA ativo</li>
</ul>

<p>A reter também: em caso de <strong>cessação de atividade</strong>, deve ser enviada uma declaração à repartição de finanças no prazo de <strong>quinze dias</strong>. A isenção cessa na data da cessação.</p>

<h2>Periodicidade das declarações após a passagem</h2>

<p>Uma vez no regime normal, a periodicidade depende do volume de negócios anual sem IVA. <strong>A declaração anual acresce às declarações periódicas, não as substitui:</strong></p>

<ul>
    <li><strong>Volume de negócios &lt; 112 000 €</strong>: apenas declaração anual</li>
    <li><strong>Entre 112 000 € e 620 000 €</strong>: declarações trimestrais <strong>e</strong> declaração anual</li>
    <li><strong>Superior a 620 000 €</strong>: declarações mensais <strong>e</strong> declaração anual</li>
</ul>

<p>É a AED que determina o seu regime — não é o contribuinte que o escolhe.</p>

<h2>Impacto nas suas faturas com o faktur.lu</h2>

<p>O faktur.lu gere ambos os regimes:</p>

<ul>
    <li><strong>Isenção de IVA</strong>: as faturas apresentam automaticamente a menção «IVA não aplicável – Artigo 57bis da lei alterada de 12 de fevereiro de 1979», sem linha de IVA nem distinção</li>
    <li><strong>Regime normal</strong>: o IVA é calculado automaticamente com a taxa correta (17 %, 14 %, 8 % ou 3 %)</li>
    <li><strong>Alerta de limiar</strong>: o faktur.lu avisa-o quando se aproxima do limiar de 50 000 € (e da tolerância a 55 000 €)</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os limiares, taxas e procedimentos de IVA podem evoluir, e o regime transfronteiriço é recente. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Regime especial das pequenas empresas (isenção)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/tva/sme/faq-fr.pdf" target="_blank" rel="noopener">AED – FAQ Isenção (PDF)</a></li>
    <li><a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">Comissão Europeia – Limiares nacionais de isenção por Estado-Membro</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Lei alterada de 12 de fevereiro de 1979 (LIVA)</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/PT/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Diretiva (UE) 2020/285</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">Taxas de IVA no Luxemburgo →</a></li><li><a href="/pt/blog/freelance-luxembourg-facturer-conformite" class="text-primary-500 hover:text-primary-600 text-sm">Freelancer Luxemburgo: faturar em conformidade →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Menções obrigatórias numa fatura luxemburguesa →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Faça a gestão da sua isenção de IVA com tranquilidade</h3>
    <p class="text-primary-800 mb-4">O faktur.lu adapta-se automaticamente ao seu regime de IVA e avisa-o antes de ultrapassar o limiar de 50 000 €.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
PTBODY,

        "freelance-luxembourg-facturer-conformite" => <<<'PTBODY'
<p class="lead">Está a lançar a sua atividade como freelancer no Luxemburgo? A faturação é um aspeto crucial da sua atividade. Este guia explica-lhe como criar faturas conformes em 2026 e gerir as suas obrigações fiscais (atualizado na sequência do aumento do limiar de isenção de IVA para 50 000 €).</p>

<h2>O estatuto de freelancer no Luxemburgo</h2>

<p>No Luxemburgo, o freelancer (ou trabalhador independente) exerce geralmente sob um destes estatutos:</p>

<ul>
    <li><strong>Empresa em nome individual</strong>: o estatuto mais comum para começar</li>
    <li><strong>Sociedade unipessoal (SARL-S)</strong>: uma sociedade por quotas simplificada</li>
    <li><strong>Profissão liberal</strong>: para certas atividades regulamentadas</li>
</ul>

<p>Seja qual for o seu estatuto, aplicam-se as mesmas regras de faturação.</p>

<h2>Registar-se no IVA</h2>

<p>Antes de começar a faturar (acima do limiar de isenção, ver abaixo), deve obter um <strong>número de IVA intracomunitário</strong> junto da <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administração do registo, dos domínios e do IVA)</a>.</p>

<h3>Procedimento de registo</h3>

<ol>
    <li>Obter uma <strong>autorização de estabelecimento</strong> junto do Ministério da Economia</li>
    <li>Inscrever-se no <strong>Registo Comercial (RCS)</strong> se aplicável</li>
    <li>Pedir o <strong>registo de IVA</strong> através do <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a></li>
    <li>Receber o seu número no formato <strong>LU + 8 dígitos</strong></li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Atenção</p>
    <p class="text-amber-700">Abaixo do limiar de isenção (volume de negócios ≤ 50 000 € sem IVA, art. 57bis LIVA desde 2025), o registo de IVA não é obrigatório. Fatura então sem IVA com a menção «IVA não aplicável - Artigo 57bis». <strong>Contrapartida:</strong> não recupera o IVA das suas compras profissionais (equipamento, software, subcontratação). Se investe, a isenção pode custar-lhe mais do que lhe rende.</p>
</div>

<h2>As menções obrigatórias nas suas faturas</h2>

<p>Segundo o <strong>artigo 63 LIVA</strong>, enquanto freelancer, as suas faturas devem conter:</p>

<h3>As suas informações</h3>

<ul>
    <li><strong>Nome completo</strong> ou denominação social</li>
    <li><strong>Endereço profissional</strong> no Luxemburgo</li>
    <li><strong>Número de IVA</strong> (LU12345678)</li>
    <li>Eventualmente o seu número RCS (obrigatório para comerciantes/sociedades inscritos)</li>
</ul>

<h3>Informações do cliente</h3>

<ul>
    <li>Nome ou denominação social</li>
    <li>Endereço completo</li>
    <li>Número de IVA (obrigatório para clientes profissionais intra-UE)</li>
</ul>

<h3>Detalhes da prestação</h3>

<ul>
    <li><strong>Número de fatura</strong> único e sequencial (artigo 63 LIVA, ponto 3°)</li>
    <li><strong>Data de emissão</strong></li>
    <li><strong>Data da prestação</strong> (facto gerador do IVA)</li>
    <li><strong>Descrição detalhada</strong> dos serviços prestados</li>
    <li><strong>Número de horas ou dias</strong> (recomendado)</li>
    <li><strong>Preço unitário sem IVA</strong></li>
    <li><strong>Montante sem IVA, IVA e total</strong></li>
    <li><strong>Taxa de IVA aplicável</strong></li>
</ul>

<h2>Que taxa de IVA aplicar?</h2>

<p>Enquanto freelancer, aplica geralmente a <strong>taxa normal de 17 %</strong> à maioria das prestações de serviços.</p>

<h3>Casos particulares</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situação</th>
            <th class="border border-gray-300 px-4 py-2 text-left">IVA aplicável</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente profissional no Luxemburgo</td><td class="border border-gray-300 px-4 py-2">IVA 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente profissional na UE (B2B intra-UE)</td><td class="border border-gray-300 px-4 py-2">0 % (autoliquidação, art. 17 LIVA + art. 196 diretiva)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente fora da UE</td><td class="border border-gray-300 px-4 py-2">0 % (fora do âmbito do IVA luxemburguês para a maioria das prestações intelectuais)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente particular no Luxemburgo</td><td class="border border-gray-300 px-4 py-2">IVA 17 %</td></tr>
    </tbody>
</table>

<h2>Faturar a um cliente no estrangeiro</h2>

<h3>Cliente profissional na UE</h3>

<p>Se o seu cliente for uma empresa noutro país da UE:</p>

<ol>
    <li><strong>Verifique o número de IVA</strong> no sistema <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a></li>
    <li><strong>Não aplique IVA</strong> na sua fatura (lugar de tributação no adquirente, art. 17 LIVA)</li>
    <li><strong>Acrescente a menção</strong>: <em>«Autoliquidação - Artigo 196.º da Diretiva 2006/112/CE»</em></li>
    <li><strong>Indique o número de IVA</strong> do cliente na fatura</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Menção obrigatória - base legal correta</p>
    <p>A menção canónica para a autoliquidação B2B intra-UE é <strong>«Autoliquidação - Artigo 196.º da Diretiva 2006/112/CE»</strong>. O artigo 196.º designa o adquirente como devedor (art. 226.º §11bis da diretiva). Não confundir com o artigo 44.º da diretiva (lugar de tributação) nem com o artigo 44.º da lei luxemburguesa de 1979 (isenções setoriais, sem relação).</p>
</div>

<h3>Cliente fora da UE</h3>

<p>Para clientes situados fora da União Europeia, as prestações intelectuais correntes do freelancer (consultoria, informática, publicidade, tradução) ficam fora do âmbito do IVA luxemburguês. Mencione «IVA não aplicável - serviço localizado fora da UE».</p>

<h2>A numeração das suas faturas (artigo 63 LIVA, ponto 3°)</h2>

<p>As suas faturas devem seguir uma <strong>numeração cronológica e contínua</strong>:</p>

<ul>
    <li>Sem falhas na sequência</li>
    <li>Formato livre mas coerente (ex.: 2026-001, 2026-002…)</li>
    <li>Reinício anual autorizado</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Conselho</p>
    <p class="text-purple-700">Utilize um software de faturação como o faktur.lu para gerar automaticamente números conformes e evitar erros.</p>
</div>

<h2>Gerir as suas declarações de IVA</h2>

<p>Uma vez registado, entrega declarações cuja periodicidade depende do seu volume de negócios sem IVA. <strong>É a AED que determina o seu regime</strong> — não é o contribuinte que o escolhe.</p>

<ul>
    <li><strong>Volume de negócios &lt; 112 000 €/ano</strong>: apenas declaração anual</li>
    <li><strong>Entre 112 000 € e 620 000 €/ano</strong>: declarações trimestrais <strong>e</strong> declaração anual</li>
    <li><strong>Superior a 620 000 €/ano</strong>: declarações mensais <strong>e</strong> declaração anual</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ A declaração anual não substitui as declarações periódicas</p>
    <p class="text-amber-700">É o equívoco mais dispendioso desta página. Se entrega declarações trimestrais ou mensais, deve <strong>além disso</strong> entregar uma <strong>declaração anual recapitulativa</strong>, até <strong>1 de maio do ano seguinte</strong>. Esquecê-la desencadeia penalizações por entrega tardia, mesmo tendo pago todo o seu IVA ao longo do ano.</p>
</div>

<p>As declarações periódicas entregam-se até ao dia 15 do mês seguinte ao período em causa. Tudo se faz em linha através do <strong>eCDF (eTVA)</strong>.</p>

<h2>O ficheiro FAIA para os freelancers</h2>

<p>Se utiliza um software de faturação, deve ser capaz de produzir um <strong>ficheiro FAIA</strong> a pedido da AED - sob condições. As FAQ da AED excluem os sujeitos passivos com volume de negócios ≤ 112 000 € sem IVA, pelo que os freelancers muito pequenos em regime de isenção não são geralmente obrigados a produzir o FAIA. A confirmar com o seu contabilista consoante o regime.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ O faktur.lu gera o seu ficheiro FAIA</p>
    <p class="text-green-700">O nosso software produz automaticamente um ficheiro FAIA 2.01 conforme, pronto para qualquer inspeção fiscal da AED.</p>
</div>

<h2>Conselhos para freelancers iniciantes</h2>

<ol>
    <li><strong>Utilize um software adequado</strong> desde o início para evitar erros</li>
    <li><strong>Conserve todas as faturas</strong> (emitidas e recebidas) durante 10 anos (art. 16 Código Comercial + art. 65 LIVA)</li>
    <li><strong>Separe</strong> as contas pessoais e profissionais</li>
    <li><strong>Fature rapidamente</strong> (até ao dia 15 do mês seguinte à prestação, art. 63 LIVA)</li>
    <li><strong>Verifique os números de IVA</strong> dos seus clientes da UE antes de faturar (VIES)</li>
    <li><strong>Anote os seus dois prazos</strong>: as declarações periódicas e a declaração anual</li>
    <li><strong>Consulte um contabilista</strong> para questões complexas</li>
</ol>

<h2>Erros correntes a evitar</h2>

<ul>
    <li>❌ Faturar sem número de IVA (se lhe for exigível)</li>
    <li>❌ Esquecer menções obrigatórias (art. 63 LIVA)</li>
    <li>❌ Aplicar uma taxa de IVA errada</li>
    <li>❌ Numeração não sequencial</li>
    <li>❌ Faturar depois do dia 15 do mês seguinte à prestação</li>
    <li>❌ Não verificar os números de IVA da UE (VIES)</li>
    <li>❌ <strong>Julgar que as declarações trimestrais dispensam a declaração anual</strong></li>
    <li>❌ Confundir «artigo 44.º da diretiva» (lugar de tributação) e «artigo 196.º da diretiva» (menção de autoliquidação)</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os limiares, taxas e procedimentos de IVA podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Lei alterada de 12 de fevereiro de 1979 (LIVA) - artigos 17, 57bis, 63, 65</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu - Declarações de IVA e periodicidades</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED - Regime de isenção (art. 57bis)</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED - Menções obrigatórias nas faturas</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - Validação de IVA intracomunitário</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<h2>Conclusão</h2>

<p>Faturar como freelancer no Luxemburgo não é complicado se seguir as regras. Utilizar um software de faturação adequado como o faktur.lu permite-lhe criar faturas conformes em poucos cliques, com todas as menções obrigatórias e as taxas de IVA corretas aplicadas automaticamente. Concentre-se no seu ofício, nós tratamos da sua conformidade!</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/5-erreurs-frequentes-facture-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">5 erros frequentes numa fatura de freelancer →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Menções obrigatórias numa fatura luxemburguesa →</a></li><li><a href="/pt/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Isenção de IVA no Luxemburgo (limiar 50 000 €) →</a></li></ul></div>
PTBODY,

        "livre-des-recettes-luxembourg-obligations-modele" => <<<'PTBODY'
<p class="lead">O <strong>livro de receitas</strong> é um documento contabilístico simples, mantido no Luxemburgo por independentes, freelancers e pequenas empresas que não fazem contabilidade por partidas dobradas. Regista cronologicamente todas as receitas cobradas. Eis tudo o que é preciso saber em 2026.</p>

<h2>O que é o livro de receitas?</h2>

<p>O livro de receitas é um registo cronológico de todas as faturas emitidas e dos pagamentos recebidos pela sua empresa. É a forma mais simples da contabilidade exigida por lei.</p>

<p>O fundamento é o <strong>artigo 65.º, parágrafo 2, da lei do IVA</strong>, que impõe a qualquer sujeito passivo «manter uma contabilidade suficientemente detalhada para permitir a aplicação do IVA e o seu controlo pela administração». A lei não prescreve um modelo único: fixa um resultado a atingir. Para um independente em contabilidade simplificada, o livro de receitas é a forma habitual de lá chegar.</p>

<p>Ao contrário do livro razão, trata-se de um documento <strong>simplificado</strong>, destinado a <strong>independentes, freelancers e pequenas empresas</strong> sem contabilidade por partidas dobradas.</p>

<h2>Quem deve manter um livro de receitas?</h2>

<ul>
    <li><strong>Os independentes e profissões liberais</strong> que não mantêm uma contabilidade comercial completa</li>
    <li><strong>As pequenas empresas em nome individual</strong>, incluindo em isenção de IVA (artigo 57bis LIVA, limiar de 50 000 € sem IVA desde 1 de janeiro de 2025)</li>
    <li><strong>Os comerciantes em contabilidade simplificada</strong> cujo volume de negócios não exceda o limiar da contabilidade comercial completa</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A reter</p>
    <p>As <strong>sociedades com contabilidade por partidas dobradas</strong> (SARL, SA, etc.) não têm de manter um livro de receitas separado: o razão e os diários cumprem essa função. A prática visa sobretudo os independentes em contabilidade simplificada.</p>
</div>

<h2>O que deve conter o livro de receitas?</h2>

<p>Cada registo deve indicar:</p>

<ul>
    <li><strong>A data</strong> da fatura ou do pagamento</li>
    <li><strong>O número da fatura</strong> (numeração sequencial, art. 63 LIVA)</li>
    <li><strong>O nome do cliente</strong></li>
    <li><strong>A descrição</strong> da prestação ou do bem vendido</li>
    <li><strong>O montante sem IVA</strong></li>
    <li><strong>A taxa de IVA aplicada</strong> (17 %, 14 %, 8 %, 3 % ou 0 %)</li>
    <li><strong>O montante do IVA</strong></li>
    <li><strong>O montante com IVA</strong></li>
</ul>

<h2>Formato e conservação</h2>

<p>O livro de receitas pode ser mantido:</p>

<ul>
    <li><strong>Em papel</strong>: num caderno dedicado, sem rasuras nem espaços em branco</li>
    <li><strong>Em formato digital</strong>: através de um software de faturação, uma folha de cálculo ou um PDF, com garantias de integridade</li>
</ul>

<p>Deve ser conservado <strong>dez anos a contar do seu encerramento</strong> (art. 65.º parágrafo 4 da lei do IVA e artigo 16 do Código Comercial). Repare na nuance: para os <strong>livros</strong>, o prazo corre desde o encerramento, ao passo que para as <strong>faturas</strong> corre desde a data de emissão. Ver as <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">obrigações contabilísticas no Guichet.lu</a>.</p>

<h2>Gerar o livro de receitas com o faktur.lu</h2>

<p>O livro de receitas é construído automaticamente a partir das suas faturas — não tem nada a reintroduzir:</p>

<ol>
    <li>Vá a <strong>Contabilidade &gt; Livro de receitas</strong></li>
    <li>Selecione o período pretendido (mês, trimestre, ano)</li>
    <li>Consulte o detalhe de cada fatura com repartição do IVA</li>
    <li>Exporte em <strong>PDF</strong> ou <strong>CSV</strong> para o seu contabilista</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Disponível a partir do plano Essentiel</p>
    <p>O livro de receitas faz parte das exportações contabilísticas, incluídas nos planos <strong>Essentiel</strong> e <strong>Pro</strong>. O plano gratuito não o propõe — em contrapartida, a <strong>exportação FAIA está aí incluída</strong>, o que não é intuitivo: as duas funcionalidades não estão ao mesmo nível de oferta.</p>
</div>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A não confundir: AED e ACD</p>
    <p>A <strong>AED</strong> (Administração do registo, dos domínios e do IVA) trata do IVA e das inspeções relativas às faturas. A <strong>ACD</strong> (Administração das contribuições diretas) trata do imposto sobre o rendimento e do imposto sobre as sociedades. O livro de receitas serve <strong>a ambas</strong>: à AED como documento de IVA, à ACD como base do seu resultado tributável. Um documento, duas administrações.</p>
</div>

<h2>Livro de receitas ou exportação FAIA?</h2>

<p>Não confunda os dois:</p>

<ul>
    <li>O <strong>livro de receitas</strong> é um documento de acompanhamento corrente, usado no dia a dia e entregue ao contabilista</li>
    <li>O <strong>FAIA</strong> é um ficheiro XML estruturado (norma SAF-T adaptada pela AED), exigido apenas numa inspeção, e apenas se <strong>quatro condições estiverem reunidas ao mesmo tempo</strong>: estar sujeito ao plano de contas normalizado, não beneficiar de um regime simplificado, ultrapassar 112 000 € de volume de negócios e cerca de 500 transações contabilísticas por ano</li>
</ul>

<p>Por outras palavras: se mantém um livro de receitas por estar em contabilidade simplificada, muito provavelmente <strong>não</strong> está sujeito ao FAIA. Ver o nosso <a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide">guia completo do FAIA</a>.</p>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Lei do IVA coordenada - artigo 65.º (contabilidade e conservação)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Obrigações contabilísticas das empresas</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Portal da fiscalidade indireta</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 31 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Isenção de IVA no Luxemburgo →</a></li><li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">O ficheiro FAIA →</a></li><li><a href="/pt/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">Arquivo das faturas →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">O seu livro de receitas, sem reintrodução</h3>
    <p class="text-primary-800 mb-4">O faktur.lu constrói o seu livro de receitas a partir das suas faturas e exporta-o em PDF ou CSV para o seu contabilista. Incluído a partir do plano Essentiel.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
PTBODY,

        "mentions-obligatoires-facture-luxembourg" => <<<'PTBODY'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Em resumo</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li>As menções obrigatórias decorrem do <strong>artigo 63 LIVA</strong>: identidade e endereço das partes, números de IVA, data, <strong>número sequencial único</strong>, designação, base sem IVA, taxa, montante de IVA, total com IVA.</li><li>Uma fatura <strong>não conforme</strong> pode ser rejeitada pelo cliente e originar uma <strong>coima da AED de 250 € a 10 000 €</strong> (art. 77 LIVA) — ou <strong>10 % a 50 % do IVA em causa</strong> se o Estado tiver perdido receita.</li><li>Menções condicionais: <strong>autoliquidação</strong> (art. 196 diretiva), isenção, exonerações.</li><li><strong>Conservação 10 anos</strong> das faturas e documentos contabilísticos.</li></ul></div>
<p class="lead">Uma fatura não conforme pode ser rejeitada pelo seu cliente e expô-lo a coimas da AED. Eis a checklist completa das menções obrigatórias para criar faturas luxemburguesas irrepreensíveis — com base no <a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">artigo 63 LIVA</a> e na lista publicada pela AED.</p>

<h2>Checklist das menções obrigatórias</h2>

<p>O <strong>artigo 63 LIVA</strong> (lei alterada de 12 de fevereiro de 1979) e a lista da AED impõem os seguintes elementos:</p>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações sobre o emitente</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nome completo ou denominação social</strong></li>
        <li>☐ <strong>Endereço completo</strong> da sede</li>
        <li>☐ <strong>Número de IVA intracomunitário</strong> (formato LU + 8 dígitos)</li>
        <li>☐ <strong>Número RCS</strong> para comerciantes e sociedades inscritos — obrigação decorrente da <em>legislação sobre o registo comercial e das sociedades</em>, e não da lei do IVA</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações sobre o cliente</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nome completo ou denominação social</strong></li>
        <li>☐ <strong>Endereço completo</strong></li>
        <li>☐ <strong>Número de IVA</strong> (obrigatório para B2B intracomunitário - validado via <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a>)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações sobre a fatura</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Número de fatura único e sequencial</strong> (art. 63 LIVA, ponto 3°)</li>
        <li>☐ <strong>Data de emissão</strong> da fatura</li>
        <li>☐ <strong>Data de entrega ou prestação</strong> — legalmente exigida <em>quando difere</em> da data de emissão (art. 226 §7 da diretiva). Indicá-la sistematicamente continua a ser boa prática: elimina qualquer ambiguidade sobre o facto gerador numa inspeção</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Detalhe dos bens ou serviços</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Descrição clara</strong> e detalhada</li>
        <li>☐ <strong>Quantidade</strong> entregue ou prestada</li>
        <li>☐ <strong>Preço unitário sem IVA</strong></li>
        <li>☐ <strong>Eventuais descontos ou abatimentos</strong></li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações de IVA</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Taxa de IVA</strong> aplicável por linha (17 %, 14 %, 8 %, 3 % ou 0 %)</li>
        <li>☐ <strong>Montante de IVA</strong> por taxa</li>
        <li>☐ <strong>Base tributável</strong> (montante sem IVA) por taxa de IVA</li>
        <li>☐ <strong>Menção de isenção ou autoliquidação</strong> se aplicável (ver tabela abaixo)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Totais</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Total sem IVA</strong></li>
        <li>☐ <strong>Total de IVA</strong></li>
        <li>☐ <strong>Total com IVA</strong></li>
    </ul>
</div>

<h2>Menções condicionais consoante os casos</h2>

<p>Consoante a natureza da operação, deve constar da fatura uma menção específica. Eis as formulações codificadas:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situação</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Menção a apor</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Base legal</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Serviço B2B intra-UE (adquirente noutro Estado-Membro)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">«Autoliquidação - Artigo 196.º da Diretiva 2006/112/CE»</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 17 LIVA (lugar) + art. 196.º diretiva (devedor) + art. 226.º §11bis (menção)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Entrega intra-UE de bens B2B</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">«Isenção de IVA - Artigo 138.º da Diretiva 2006/112/CE»</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 d) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Exportação fora da UE</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">«Isenção de IVA - Artigo 146.º da Diretiva 2006/112/CE»</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 a) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Isenção de IVA (pequenas empresas)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">«IVA não aplicável - Artigo 57bis da lei alterada de 12 de fevereiro de 1979»</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 57bis LIVA (limiar 50 000 €)</td>
        </tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A não confundir</p>
    <p>Para a autoliquidação B2B intra-UE, muitos modelos copiam a referência ao «artigo 44.º da Diretiva 2006/112/CE». O artigo 44.º define o <strong>lugar de tributação</strong> (no adquirente), mas a menção obrigatória codificada remete para o <strong>artigo 196.º</strong> (que designa o adquirente como devedor). Prefira a menção do artigo 196.º.</p>
</div>

<h3>Casos particulares de faturação</h3>

<p><strong>Fatura de adiantamento</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">«Adiantamento sobre a encomenda n.º [referência] de [data]»</p>
</div>

<p><strong>Nota de crédito</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">«Nota de crédito sobre a fatura n.º [número] de [data]»</p>
</div>

<h2>A numeração das faturas (art. 63 LIVA, ponto 3°)</h2>

<p>A numeração deve respeitar estas regras:</p>

<ul>
    <li><strong>Única</strong>: cada fatura tem um número distinto</li>
    <li><strong>Cronológica</strong>: os números seguem a ordem de emissão</li>
    <li><strong>Sem falhas</strong>: nenhuma lacuna na sequência</li>
    <li><strong>Não reutilizável</strong>: um número só pode ser atribuído uma vez</li>
</ul>

<h3>Exemplos de formatos aceites</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Formato</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Exemplo</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Ano + número</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">2026-0001, 2026-0002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Prefixo + número</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">FAC-001, FAC-002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Número simples</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">00001, 00002</td>
        </tr>
    </tbody>
</table>

<h2>Condições de pagamento</h2>

<p>Embora o artigo 63 LIVA não as exija, estas indicações são <strong>vivamente recomendadas</strong>:</p>

<ul>
    <li><strong>Prazo de pagamento</strong> (por defeito: 30 dias após receção, lei alterada de 18 de abril de 2004)</li>
    <li><strong>Data de vencimento</strong></li>
    <li><strong>Dados bancários</strong> (IBAN, BIC)</li>
    <li><strong>Juros de mora</strong> aplicáveis entre profissionais — taxa de referência do BCE + 8 pontos, ou seja <strong>10,15 % no 1.º semestre de 2026</strong>, revista todos os semestres — e <strong>indemnização fixa de 40 €</strong></li>
</ul>

<h2>Conservação das faturas</h2>

<p>Deve conservar as suas faturas (emitidas e recebidas) durante <strong>10 anos</strong> a contar do encerramento do exercício — artigo 16 do Código Comercial e artigo 65 LIVA. Em papel ou em formato eletrónico (PDF/A com garantias de integridade). Ver as <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">obrigações contabilísticas no Guichet.lu</a>.</p>

<h2>Consequências de uma fatura não conforme</h2>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Riscos incorridos</p>
    <ul class="text-red-700 mt-2">
        <li>Rejeição da dedução do IVA pelo seu cliente</li>
        <li><strong>Coima administrativa de 250 € a 10 000 € por infração</strong> (art. 77 LIVA)</li>
        <li>Se o incumprimento tiver conduzido a um não pagamento de IVA ou a um reembolso indevido: <strong>coima de 10 % a 50 % do montante de IVA em causa</strong> — proporcional, logo sem limite máximo</li>
        <li>Em caso de recusa de comunicar faturas e documentos contabilísticos durante uma inspeção: <strong>até 25 000 € por dia de atraso</strong>, após advertência</li>
        <li>Em caso de fraude fiscal agravada ou burla fiscal: multa penal de 25 000 € a 10 vezes o montante do IVA, prisão de um mês a cinco anos e privação dos direitos cívicos de 5 a 10 anos (art. 80 LIVA)</li>
    </ul>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">O limite de 10 000 € não é o risco máximo</p>
    <p>É a coima proporcional que dói. Num litígio sobre 80 000 € de IVA, uma sanção de 10 a 50 % representa 8 000 a 40 000 € — independentemente do número de infrações formais registadas.</p>
</div>

<h2>Exemplo de fatura conforme</h2>

<p>Eis os elementos essenciais de uma fatura conforme:</p>

<div class="bg-gray-50 border border-gray-200 rounded-lg p-6 my-6 text-sm">
    <div class="flex justify-between mb-6">
        <div>
            <p class="font-bold">A Sua Sociedade SARL</p>
            <p>123 rue du Commerce</p>
            <p>L-1234 Luxembourg</p>
            <p>TVA: LU12345678</p>
            <p>RCS: B123456</p>
        </div>
        <div class="text-right">
            <p class="font-bold text-xl">FATURA</p>
            <p>N° 2026-0042</p>
            <p>Data: 15/02/2026</p>
        </div>
    </div>

    <div class="mb-6">
        <p class="font-semibold">Faturado a:</p>
        <p>Cliente Empresa SA</p>
        <p>456 avenue des Affaires</p>
        <p>L-5678 Luxembourg</p>
        <p>TVA: LU87654321</p>
    </div>

    <table class="w-full mb-6">
        <thead class="border-b-2 border-gray-300">
            <tr>
                <th class="text-left py-2">Descrição</th>
                <th class="text-right py-2">Qtd</th>
                <th class="text-right py-2">P.U. s/IVA</th>
                <th class="text-right py-2">TVA</th>
                <th class="text-right py-2">Total s/IVA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="py-2">Prestação de consultoria</td>
                <td class="text-right">5h</td>
                <td class="text-right">150,00€</td>
                <td class="text-right">17%</td>
                <td class="text-right">750,00€</td>
            </tr>
        </tbody>
    </table>

    <div class="text-right">
        <p>Total s/IVA: <strong>750,00€</strong></p>
        <p>TVA 17%: <strong>127,50€</strong></p>
        <p class="text-lg">Total c/IVA: <strong>877,50€</strong></p>
    </div>
</div>

<h2>Simplifique a vida com o faktur.lu</h2>

<p>Criar faturas conformes manualmente é fonte de erros. O <strong>faktur.lu</strong> automatiza a conformidade:</p>

<ul>
    <li>✅ Todas as menções obrigatórias pré-preenchidas</li>
    <li>✅ Numeração automática e sequencial (art. 63 LIVA)</li>
    <li>✅ Cálculo automático do IVA consoante o caso</li>
    <li>✅ Menções legais adequadas (autoliquidação, exportação, isenção 57bis…)</li>
    <li>✅ Exportação FAIA integrada para as inspeções fiscais da AED</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>As menções obrigatórias, referências de artigos e limiares podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Lei alterada de 12 de fevereiro de 1979 (LIVA) - artigos 17, 43, 57bis, 63, 65, 77, 80</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED - Menções obrigatórias nas faturas</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">Sanções de IVA e vias de recurso</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/PT/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Diretiva 2006/112/CE - artigos 138.º, 146.º, 196.º, 226.º</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/note-de-credit-luxembourg-comment-etablir" class="text-primary-500 hover:text-primary-600 text-sm">Nota de crédito Luxemburgo →</a></li><li><a href="/pt/blog/article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire" class="text-primary-500 hover:text-primary-600 text-sm">Numeração sequencial (artigo 63 LIVA) →</a></li><li><a href="/pt/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Isenção de IVA no Luxemburgo (limiar 50 000 €) →</a></li></ul></div>
PTBODY,

        "peppol-b2g-luxembourg-guide-complet-2026" => <<<'PTBODY'
<p class="lead">Desde 2022-2023, a faturação eletrónica através do <strong>Peppol</strong> é obrigatória para os fornecedores do setor público luxemburguês, <strong>qualquer que seja o montante da fatura</strong>. Este guia explica-lhe tudo o que precisa de saber para estar em conformidade em 2026.</p>

<h2>O que é o Peppol?</h2>

<p>O <strong>Peppol (Pan-European Public Procurement OnLine)</strong> é uma rede internacional de faturação eletrónica que permite trocar documentos comerciais (faturas, notas de encomenda) de forma normalizada entre empresas e administrações públicas. Gerido pela <a href="https://peppol.org" target="_blank" rel="noopener">OpenPeppol</a>, estende-se atualmente a mais de 100 países e conta com vários milhões de participantes registados.</p>

<p>No Luxemburgo, o Peppol é o canal oficial para a faturação eletrónica B2G (Business-to-Government). Qualquer empresa que fature ao Estado, aos municípios ou a estabelecimentos públicos deve utilizar este formato.</p>

<h2>Quem é abrangido?</h2>

<p>Se for fornecedor de uma das seguintes entidades, deve faturar através do Peppol:</p>

<ul>
    <li><strong>O Estado luxemburguês</strong> e os seus ministérios</li>
    <li><strong>Os municípios luxemburgueses</strong></li>
    <li><strong>Os estabelecimentos públicos</strong> (hospitais, escolas, etc.)</li>
    <li><strong>Todos os contratos públicos e contratos de concessão</strong>, independentemente do montante</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Sem limiar de montante</p>
    <p>Ao contrário de uma ideia difundida, <strong>não existe qualquer limiar mínimo</strong> (do tipo «30 000 €») para a obrigação. A <a href="https://legilux.public.lu/eli/etat/leg/loi/2021/12/13/a869/jo" target="_blank" rel="noopener">lei de 13 de dezembro de 2021</a> impõe a faturação eletrónica para <strong>todas</strong> as faturas emitidas no âmbito de um contrato público ou de concessão, do mais pequeno ao maior montante.</p>
</div>

<h3>Calendário de aplicação</h3>

<p>A obrigação entrou em vigor de forma faseada, consoante a dimensão do operador económico:</p>

<ul>
    <li><strong>18 de maio de 2022</strong>: grandes operadores económicos</li>
    <li><strong>18 de outubro de 2022</strong>: operadores económicos de média dimensão</li>
    <li><strong>18 de março de 2023</strong>: pequenos operadores e empresas recém-criadas</li>
</ul>

<p>Desde 18 de março de 2023, <strong>todos</strong> os fornecedores do setor público luxemburguês estão, portanto, abrangidos.</p>

<h2>Como funciona o Peppol?</h2>

<p>A rede Peppol assenta num modelo de quatro cantos (4-corner model):</p>

<ol>
    <li><strong>O emissor</strong> (a sua empresa) cria a fatura</li>
    <li><strong>O ponto de acesso emissor</strong> (o seu software de faturação ou o respetivo Access Point) coloca a fatura na rede Peppol</li>
    <li><strong>O ponto de acesso destinatário</strong> (do lado da administração) recebe a fatura</li>
    <li><strong>O destinatário</strong> (a administração pública) processa a fatura</li>
</ol>

<p>Cada participante na rede é identificado por um <strong>Peppol Participant ID</strong> único. No Luxemburgo, o esquema padrão é o <strong>9938</strong> (LU:VAT), baseado no número de IVA. Formato: <code>9938:LU########</code>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">⚠ Erro clássico: esquema 0184</p>
    <p>Muitas fontes confundem. O esquema <strong>0184</strong> corresponde ao registo de empresas <strong>dinamarquês</strong> (DIGSTORG/CVR), não ao Luxemburgo. O esquema LU correto é o <strong>9938</strong>. Verifique na <a href="https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/" target="_blank" rel="noopener">lista de códigos EAS oficial do Peppol</a>.</p>
</div>

<h2>O formato UBL e o Peppol BIS Billing 3.0</h2>

<p>As faturas Peppol no Luxemburgo utilizam o formato <strong>Peppol BIS Billing 3.0</strong>, assente no padrão XML <strong>UBL (Universal Business Language)</strong> e conforme à norma europeia <strong>EN 16931</strong>. A sua fatura deve conter:</p>

<ul>
    <li>As informações do emissor (nome, endereço, n.º de IVA)</li>
    <li>As informações do destinatário (nome, Peppol Participant ID)</li>
    <li>As linhas de faturação (descrição, quantidade, preço unitário)</li>
    <li>Os montantes de IVA repartidos por taxa</li>
    <li>Os totais (sem IVA, IVA, com IVA)</li>
    <li>As referências de encomenda ou de contrato</li>
</ul>

<p>O formato <strong>XRechnung 3.0.1</strong> (norma alemã conforme à EN 16931) é igualmente aceite pelas administrações luxemburguesas.</p>

<h2>Alternativas se não estiver ligado ao Peppol</h2>

<p>Se ainda não tiver software ligado ao Peppol, pode utilizar o canal alternativo oficial:</p>

<ul>
    <li><strong>MyGuichet.lu</strong>: formulários em linha que permitem submeter manualmente uma fatura eletrónica conforme às administrações destinatárias</li>
</ul>

<p>Veja o procedimento completo em <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/marche-public-concession/facturation/transmission-facture-electronique-marche-public-contrat-concession.html" target="_blank" rel="noopener">Guichet.lu - Transmissão de uma fatura eletrónica para um contrato público</a>.</p>

<h2>Peppol e faktur.lu: o que está disponível hoje</h2>

<p>Preferimos ser precisos neste ponto a assinalar uma caixa.</p>

<p><strong>Disponível desde já:</strong> o faktur.lu gera o <strong>ficheiro Peppol BIS Billing 3.0 (UBL)</strong> das suas faturas, conforme ao formato exigido pelo setor público luxemburguês. Descarrega-o a partir da fatura e transmite-o pelo canal da sua escolha — nomeadamente o ponto de acesso do <strong>CTIE</strong>, que os organismos públicos sem ponto próprio utilizam.</p>

<p><strong>Em entrada em produção:</strong> a transmissão automática através de um ponto de acesso integrado, que lhe poupará esse passo manual. O formato está tecnicamente pronto, a implementação para todas as contas ainda não. Atualizaremos esta página no dia em que estiver.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Entretanto, está em conformidade</p>
    <p>A obrigação legal incide sobre o <strong>formato</strong> da fatura e a sua transmissão eletrónica, não sobre a ferramenta usada para a entregar. Um ficheiro BIS 3.0 conforme, submetido pelo canal do seu comprador público, cumpre a obrigação.</p>
</div>

<h2>Vantagens da faturação Peppol</h2>

<ul>
    <li><strong>Processamento acelerado</strong>: as faturas Peppol são processadas automaticamente, reduzindo os prazos de pagamento</li>
    <li><strong>Menos erros</strong>: o formato estruturado elimina os erros de introdução manual</li>
    <li><strong>Rastreabilidade</strong>: cada fatura é seguida de ponta a ponta na rede</li>
    <li><strong>Conformidade</strong>: cumpre as obrigações legais luxemburguesas (lei de 13 de dezembro de 2021)</li>
    <li><strong>Alcance internacional</strong>: o Peppol está atualmente implantado em mais de <strong>100 países</strong>, com dezenas de Peppol Authorities nacionais</li>
</ul>

<h2>Perguntas frequentes</h2>

<h3>A faturação Peppol é obrigatória no B2B?</h3>

<p>Ainda não no Luxemburgo para o B2B doméstico. O pacote europeu <strong>ViDA (VAT in the Digital Age)</strong>, adotado em 2025, prevê a obrigação de faturação eletrónica para as operações <strong>B2B intracomunitárias</strong> a partir de <strong>1 de julho de 2030</strong>. Para o B2B doméstico, a harmonização completa está prevista até 2035. Ver <a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Comissão Europeia - ViDA</a>.</p>

<h3>Quanto custa o envio através do Peppol?</h3>

<p>A geração do ficheiro Peppol BIS 3.0 está incluída na sua subscrição faktur.lu, sem custo adicional por fatura. A própria rede Peppol não fatura ao emissor: o eventual custo depende do ponto de acesso utilizado para o encaminhamento.</p>

<h3>Como encontrar o Peppol ID de uma administração?</h3>

<p>Os Peppol Participant IDs das administrações luxemburguesas estão disponíveis no <a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a>. Também os pode procurar diretamente no faktur.lu ao criar o cliente.</p>

<h3>Qual é o esquema Peppol no Luxemburgo?</h3>

<p>O esquema padrão é o <strong>9938</strong> (LU:VAT, baseado no número de IVA). Formato completo: <code>9938:LU########</code>. Não confundir com 0184, o esquema dinamarquês.</p>

<h3>Quais são as sanções em caso de não conformidade?</h3>

<p>A administração pode recusar o processamento de uma fatura em papel ou PDF não conforme e exigir a sua retransmissão através do Peppol, o que atrasa o pagamento. Para o detalhe das sanções formais, consulte a lei de 13 de dezembro de 2021 ou o seu contabilista.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>O calendário ViDA, os limiares e as Peppol Authorities evoluem. Esta página é atualizada regularmente, mas para a sua situação consulte o <a href="https://guichet.public.lu/" target="_blank" rel="noopener">Guichet.lu</a> e a documentação oficial do Peppol.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2021/12/13/a869/jo" target="_blank" rel="noopener">Lei de 13 de dezembro de 2021 sobre a faturação eletrónica B2G</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/marche-public-concession/facturation/transmission-facture-electronique-marche-public-contrat-concession.html" target="_blank" rel="noopener">Guichet.lu - Transmissão de fatura eletrónica</a></li>
    <li><a href="https://mindigital.gouvernement.lu/fr/dossiers/2021/facturation-electronique.html" target="_blank" rel="noopener">Ministério da Digitalização - Faturação eletrónica</a></li>
    <li><a href="https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/" target="_blank" rel="noopener">Peppol - Lista de códigos EAS (esquemas de identificadores)</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Comissão Europeia - VAT in the Digital Age (ViDA)</a></li>
    <li><a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 31 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/factur-x-zugferd-facturation-electronique-europeenne" class="text-primary-500 hover:text-primary-600 text-sm">Factur-X / ZUGFeRD →</a></li><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Escolher software de faturação →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Menções obrigatórias numa fatura LU →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Pronto para faturar através do Peppol?</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera o ficheiro Peppol BIS Billing 3.0 conforme das suas faturas, pronto a ser transmitido ao setor público. Crie a sua conta gratuita e experimente em poucos minutos.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
PTBODY,

        "relancer-client-impaye-luxembourg" => <<<'PTBODY'
<p class="lead">As faturas por pagar são o pesadelo de qualquer empresário. No Luxemburgo, a <a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">lei de 18 de abril de 2004</a> enquadra os prazos de pagamento e os juros de mora. Eis como gerir as cobranças com eficácia, do lembrete amigável à interpelação formal.</p>

<h2>Etapa 1: O lembrete amigável (D+7 após o vencimento)</h2>

<p>Simples esquecimento? É frequentemente o caso. O primeiro lembrete deve ser <strong>cortês e profissional</strong>:</p>

<ul>
    <li>Envie um e-mail relembrando o número e o montante da fatura</li>
    <li>Anexe uma cópia da fatura original</li>
    <li>Proponha um novo prazo de pagamento, se necessário</li>
    <li>Mantenha-se factual e cordial</li>
</ul>

<p><strong>Conselho:</strong> com o faktur.lu pode automatizar este primeiro lembrete. O sistema deteta as faturas em atraso e envia automaticamente um e-mail de cobrança.</p>

<h2>Etapa 2: A cobrança formal (D+15)</h2>

<p>Se o primeiro lembrete ficar sem resposta, envie uma <strong>cobrança mais formal</strong>:</p>

<ul>
    <li>Refira claramente o atraso de pagamento</li>
    <li>Relembre as condições de pagamento acordadas</li>
    <li>Indique que poderão ser aplicados juros de mora</li>
    <li>Fixe um prazo preciso (por exemplo, 8 dias)</li>
</ul>

<h2>Etapa 3: A interpelação formal (D+30)</h2>

<p>A interpelação formal (<em>mise en demeure</em>) é um documento oficial que constitui uma <strong>prova jurídica</strong>. Embora o Luxemburgo não imponha qualquer forma legal, o envio por <strong>carta registada com aviso de receção</strong> é vivamente recomendado. Deve conter:</p>

<ul>
    <li>A menção <strong>«Interpelação formal»</strong> no assunto</li>
    <li>O detalhe das faturas por pagar (números, montantes, datas)</li>
    <li>O montante total devido (capital + eventuais juros de mora + indemnização fixa de 40 €)</li>
    <li>Um <strong>prazo de 8 dias</strong> para regularizar (duração habitual)</li>
    <li>A menção de que se reserva o direito de intentar uma ação judicial</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A reter</p>
    <p>Ao abrigo da lei de 18 de abril de 2004, os juros de mora começam a correr a partir do vencimento contratual, <strong>sem que seja necessária uma interpelação formal</strong>. Esta continua, no entanto, a ser útil para abrir a via judicial e comprovar o crédito.</p>
</div>

<h2>Juros de mora no Luxemburgo</h2>

<p>No Luxemburgo, os juros de mora são regidos pela <strong>lei de 18 de abril de 2004</strong> sobre os prazos de pagamento:</p>

<ul>
    <li><strong>Transações B2B e B2G</strong>: taxa de juro = <strong>taxa diretora do BCE + 8 pontos percentuais</strong></li>
    <li><strong>Taxa no 1.º semestre de 2026: 10,15 %</strong> (BCE 2,15 % + 8)</li>
    <li><strong>Indemnização fixa</strong>: 40 € para despesas de cobrança (sem justificativo, art. 6.º da lei)</li>
    <li>Prazos legais por defeito: 30 dias B2B e B2G (máx. 60 dias B2B por contrato, se não abusivo)</li>
</ul>

<p>⚠️ Esta taxa é <strong>revista semestralmente</strong> e publicada no Mémorial. Verifique sempre <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">a taxa em vigor</a> no momento da sua cobrança.</p>

<h2>Etapa 4: A cobrança coerciva (D+60)</h2>

<p>Se todas as cobranças falharem, dispõe de várias opções:</p>

<ul>
    <li><strong>Empresa de cobrança</strong>: encarrega-se das diligências mediante comissão (15–25 %)</li>
    <li><strong>Injunção de pagamento</strong>: procedimento simplificado perante o juiz de paz (para créditos ≤ 15 000 €)</li>
    <li><strong>Ação em tribunal</strong>: para montantes mais elevados</li>
    <li><strong>Mediação comercial</strong>: alternativa mais rápida e menos onerosa</li>
</ul>

<h2>Boas práticas para evitar incumprimentos</h2>

<ul>
    <li><strong>Fature rapidamente</strong>: quanto mais esperar, maior o risco de incumprimento</li>
    <li><strong>Condições claras</strong>: indique prazos e modalidades de pagamento em cada fatura</li>
    <li><strong>Adiantamento</strong>: peça 30–50 % de sinal nas prestações de maior dimensão</li>
    <li><strong>Cobranças automáticas</strong>: use um software como o faktur.lu para nunca se esquecer de cobrar</li>
    <li><strong>Verifique a solvabilidade</strong>: para novos clientes, consulte o <a href="https://www.lbr.lu/" target="_blank" rel="noopener">Registo Comercial do Luxemburgo (LBR)</a></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>A taxa de juro legal evolui semestralmente e a indemnização fixa pode ser revista. Esta página é atualizada regularmente, mas consulte <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">a taxa em vigor</a> no momento da sua cobrança.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2004/04/18/n8/jo" target="_blank" rel="noopener">Lei de 18 de abril de 2004 sobre os prazos de pagamento</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu – Juros de mora</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministério da Justiça – Taxa de juro legal em vigor</a></li>
    <li><a href="https://justice.public.lu/fr/creances/recouvrement-creances.html" target="_blank" rel="noopener">Justice.lu – Cobrança de créditos</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/delais-paiement-luxembourg-cadre-legal-2026" class="text-primary-500 hover:text-primary-600 text-sm">Prazos de pagamento legais no LU →</a></li><li><a href="/pt/blog/automatiser-facturation-7-conseils-gagner-temps" class="text-primary-500 hover:text-primary-600 text-sm">Automatizar a faturação →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatize as suas cobranças com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">O faktur.lu deteta automaticamente as faturas em atraso e envia cobranças por e-mail. Nunca mais deixe uma fatura por pagar cair no esquecimento.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
PTBODY,

        "tva-intracommunautaire-guide-entreprises-luxembourgeoises" => <<<'PTBODY'
<p class="lead">Fatura clientes noutros países da União Europeia? O <strong>IVA intracomunitário</strong> segue regras específicas que qualquer empresário luxemburguês deve dominar. Este guia explica-lhe tudo.</p>

<h2>O que é o IVA intracomunitário?</h2>

<p>O IVA intracomunitário é o regime de IVA aplicável às trocas de bens e serviços entre empresas situadas em diferentes países da <strong>União Europeia</strong>. O princípio fundamental é a <strong>autoliquidação</strong>: é o comprador, e não o vendedor, que declara e paga o IVA no seu país.</p>

<h2>O número de IVA intracomunitário</h2>

<p>No Luxemburgo, o seu número de IVA intracomunitário tem o formato <strong>LU + 8 dígitos</strong> (exemplo: LU12345678). Este número é:</p>

<ul>
    <li>Atribuído pela <strong>AED</strong> (Administração do registo, dos domínios e do IVA)</li>
    <li>Obrigatório para qualquer transação intracomunitária</li>
    <li>Verificável no <strong>sistema VIES</strong> da Comissão Europeia</li>
</ul>

<p><strong>Conselho:</strong> o faktur.lu verifica automaticamente os números de IVA através do VIES quando adiciona um cliente intracomunitário.</p>

<h2>Regras de faturação intracomunitária</h2>

<h3>Venda de serviços B2B (o caso mais frequente)</h3>

<p>Quando vende um serviço a uma empresa situada noutro país da UE:</p>

<ol>
    <li>Fatura <strong>sem IVA (0 %)</strong></li>
    <li>Menciona na fatura: <strong>«Autoliquidação – Artigo 196.º da Diretiva 2006/112/CE»</strong> (o artigo 44.º da diretiva define o lugar de tributação; é o artigo 196.º que designa o adquirente como devedor e que corresponde à menção obrigatória – ver art. 226.º §11bis da diretiva)</li>
    <li>Indica o seu número de IVA <strong>e</strong> o do cliente</li>
    <li>O cliente declara o IVA no seu próprio país (mecanismo de <em>reverse charge</em>)</li>
</ol>

<h3>Venda de bens B2B</h3>

<p>Para entregas de bens a uma empresa da UE:</p>

<ol>
    <li>Fatura <strong>sem IVA</strong> (entrega intracomunitária isenta)</li>
    <li>Menciona: <strong>«Entrega intracomunitária isenta – Artigo 138.º da Diretiva 2006/112/CE»</strong></li>
    <li>Deve provar que os bens saíram do Luxemburgo</li>
    <li>A transação deve constar do seu <strong>mapa recapitulativo</strong></li>
</ol>

<h3>Venda a um particular (B2C)</h3>

<p>As regras são diferentes para as vendas a particulares na UE. Um <strong>limiar único de 10 000 € por ano</strong> rege-as:</p>

<ul>
    <li><strong>Abaixo de 10 000 € por ano</strong>: fatura o <strong>IVA luxemburguês</strong>, como a um cliente local</li>
    <li><strong>Acima de 10 000 €</strong>: aplica o <strong>IVA do país do cliente</strong> e declara-o através do balcão único <strong>OSS</strong></li>
    <li><strong>Este limiar é comum</strong> às vendas à distância de bens <em>e</em> aos serviços eletrónicos, de telecomunicações e audiovisuais – todos os países da UE em conjunto (excluindo o Luxemburgo). O que deve acompanhar é, portanto, o <strong>total</strong> das suas vendas B2C europeias, e não cada categoria isoladamente</li>
    <li><strong>Outros serviços</strong> (consultoria, formação presencial…): geralmente IVA luxemburguês, com exceções consoante a natureza do serviço</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A ultrapassagem produz efeitos imediatos</p>
    <p>A operação que faz ultrapassar os 10 000 € é <strong>já</strong> tributável no país do cliente. As vendas anteriores permanecem tributáveis no Luxemburgo. Acompanhe o seu acumulado ao longo do ano em vez de o descobrir no fecho.</p>
</div>

<h2>Declarações obrigatórias</h2>

<p>Enquanto empresa luxemburguesa que realiza operações intracomunitárias, deve:</p>

<ul>
    <li><strong>Declaração periódica de IVA</strong>: declarar as suas operações intracomunitárias nos campos adequados</li>
    <li><strong>Mapa recapitulativo</strong>: declaração mensal ou trimestral listando todas as vendas intracomunitárias por cliente e por país</li>
    <li><strong>Intrastat</strong>: declaração estatística para os movimentos de bens acima de determinados limiares</li>
</ul>

<h2>Validação VIES: porque é crucial</h2>

<p>Antes de faturar sem IVA a um cliente da UE, <strong>deve verificar</strong> que o seu número de IVA é válido através do sistema <strong>VIES</strong> (VAT Information Exchange System). Se o número for inválido:</p>

<ul>
    <li>Deve faturar <strong>com IVA luxemburguês</strong></li>
    <li>Arrisca uma <strong>correção fiscal</strong> se faturar sem IVA e sem verificação</li>
    <li>Conserve uma <strong>prova da verificação VIES</strong> (captura de ecrã ou registo)</li>
</ul>

<p>O faktur.lu verifica automaticamente cada número de IVA intracomunitário e conserva um registo da validação.</p>

<h2>Casos práticos frequentes</h2>

<h3>Consultor luxemburguês a faturar um cliente alemão</h3>
<p>Fatura sem IVA com menção de autoliquidação. O cliente alemão declara o IVA alemão (19 %) na sua própria declaração. Declara a operação no seu mapa recapitulativo.</p>

<h3>Agência web luxemburguesa a faturar um cliente francês</h3>
<p>Mesmo princípio: fatura sem IVA, autoliquidação. O cliente francês declara 20 % de IVA francês. Deve verificar o número de IVA no VIES antes de faturar.</p>

<h3>Comércio eletrónico luxemburguês a vender a um particular belga</h3>
<p>Enquanto as suas vendas B2C europeias acumuladas (bens à distância + serviços eletrónicos) se mantiverem abaixo de 10 000 EUR/ano, fatura o IVA luxemburguês. Acima disso, aplica o IVA do país do cliente (21 % na Bélgica) através do regime <strong>OSS (One-Stop Shop)</strong>.</p>

<h2>Menções obrigatórias na fatura</h2>

<p>Qualquer fatura intracomunitária deve mencionar:</p>

<ul>
    <li>O seu número de IVA luxemburguês</li>
    <li>O número de IVA do cliente</li>
    <li>A menção legal de isenção (autoliquidação ou entrega intracomunitária)</li>
    <li>O montante sem IVA e a menção «IVA 0 %»</li>
</ul>

<p>O faktur.lu deteta automaticamente o cenário de IVA consoante o país e o tipo de cliente, e aplica as menções legais adequadas.</p>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Validação de números de IVA (Comissão Europeia)</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal da fiscalidade indireta</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/PT/TXT/?uri=CELEX%3A02006L0112-20240101" target="_blank" rel="noopener">Diretiva 2006/112/CE (texto consolidado)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">IVA no Luxemburgo →</a></li><li><a href="/pt/blog/facturer-etranger-depuis-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Faturar para o estrangeiro →</a></li><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Comparativo: escolher software de faturação →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fature em toda a UE em conformidade</h3>
    <p class="text-primary-800 mb-4">O faktur.lu deteta automaticamente os cenários de IVA intracomunitário, verifica os números de IVA através do VIES e aplica as menções legais corretas.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os limiares, taxas e procedimentos fiscais luxemburgueses podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
PTBODY,

        "tva-luxembourg-taux-calcul-obligations" => <<<'PTBODY'
<p class="lead">O IVA (Imposto sobre o Valor Acrescentado) é um elemento central da fiscalidade luxemburguesa. Compreender as diferentes taxas, saber aplicá-las corretamente e respeitar as obrigações declarativas é essencial para qualquer empresa.</p>

<h2>As taxas de IVA no Luxemburgo em 2026</h2>

<p>O Luxemburgo aplica <strong>quatro taxas de IVA</strong>, entre as mais baixas da União Europeia:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Taxa</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Nome</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Aplicação</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">17%</td>
            <td class="border border-gray-300 px-4 py-2">Taxa normal</td>
            <td class="border border-gray-300 px-4 py-2">Maioria dos bens e serviços</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">14%</td>
            <td class="border border-gray-300 px-4 py-2">Taxa intermédia</td>
            <td class="border border-gray-300 px-4 py-2">Vinhos, combustíveis sólidos, impressos publicitários</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">8%</td>
            <td class="border border-gray-300 px-4 py-2">Taxa reduzida</td>
            <td class="border border-gray-300 px-4 py-2">Gás, eletricidade, cabeleireiro</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">3%</td>
            <td class="border border-gray-300 px-4 py-2">Taxa super-reduzida</td>
            <td class="border border-gray-300 px-4 py-2">Alimentação, livros, medicamentos, transportes</td>
        </tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold text-blue-800">ℹ️ Sabia que?</p>
    <p class="text-blue-700">A taxa normal de 17% no Luxemburgo é a mais baixa da União Europeia, onde a média se situa em torno dos 21%.</p>
</div>

<h2>Detalhe das taxas por categoria</h2>

<h3>Taxa super-reduzida de 3%</h3>

<ul>
    <li>Produtos alimentares (exceto álcool e restauração)</li>
    <li>Livros, jornais e periódicos</li>
    <li>Medicamentos</li>
    <li>Transportes de pessoas</li>
    <li>Alojamento hoteleiro</li>
    <li>Entradas em eventos culturais e desportivos</li>
    <li>Cuidados médicos e dentários (não isentos)</li>
</ul>

<h3>Taxa reduzida de 8%</h3>

<ul>
    <li>Fornecimento de gás natural e de eletricidade</li>
    <li>Serviços de cabeleireiro</li>
    <li>Limpeza de vidros</li>
    <li>Pequenos serviços de reparação (bicicletas, sapatos, vestuário)</li>
</ul>

<h3>Taxa intermédia de 14%</h3>

<ul>
    <li>Vinhos (menos de 13% de álcool)</li>
    <li>Combustíveis minerais sólidos</li>
    <li>Gasóleo de aquecimento</li>
    <li>Certos impressos publicitários</li>
</ul>

<h3>Taxa normal de 17%</h3>

<p>Todos os bens e serviços que não beneficiam de uma taxa reduzida estão sujeitos à taxa normal de 17%.</p>

<h2>As operações isentas de IVA</h2>

<p>Certas operações estão <strong>isentas de IVA</strong> no Luxemburgo:</p>

<ul>
    <li>Serviços médicos e paramédicos</li>
    <li>Serviços de ensino</li>
    <li>Operações bancárias e financeiras</li>
    <li>Operações de seguros</li>
    <li>Arrendamento de bens imóveis (salvo opção)</li>
    <li>Entregas intracomunitárias (sob condições)</li>
    <li>Exportações para fora da UE</li>
</ul>

<h2>Cálculo do IVA</h2>

<h3>Calcular o IVA a partir do valor sem IVA</h3>

<p>Para calcular o montante com IVA a partir do preço sem IVA:</p>

<div class="bg-gray-100 p-4 rounded-lg my-4 font-mono">
    <p>Montante com IVA = Montante sem IVA × (1 + taxa IVA)</p>
    <p class="mt-2 text-sm text-gray-600">Exemplo: 100€ sem IVA × 1,17 = 117€ com IVA</p>
</div>

<h3>Calcular o valor sem IVA a partir do valor com IVA</h3>

<p>Para encontrar o montante sem IVA a partir do valor com IVA:</p>

<div class="bg-gray-100 p-4 rounded-lg my-4 font-mono">
    <p>Montante sem IVA = Montante com IVA ÷ (1 + taxa IVA)</p>
    <p class="mt-2 text-sm text-gray-600">Exemplo: 117€ com IVA ÷ 1,17 = 100€ sem IVA</p>
</div>

<h2>As obrigações declarativas</h2>

<p>A periodicidade depende do seu volume de negócios sem IVA. <strong>É a AED que determina o seu regime</strong> — não é o contribuinte que o escolhe.</p>

<ul>
    <li><strong>Volume de negócios inferior a 112 000 €</strong>: apenas declaração anual</li>
    <li><strong>Entre 112 000 € e 620 000 €</strong>: declarações trimestrais <strong>e</strong> declaração anual</li>
    <li><strong>Superior a 620 000 €</strong>: declarações mensais <strong>e</strong> declaração anual</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ A declaração anual não substitui as declarações periódicas</p>
    <p class="text-amber-700">Se entrega declarações trimestrais ou mensais, deve <strong>além disso</strong> entregar uma declaração anual recapitulativa, até <strong>1 de maio</strong> do ano seguinte. Esquecê-la desencadeia penalizações por entrega tardia, mesmo tendo pago todo o seu IVA ao longo do ano.</p>
</div>

<p>As declarações periódicas entregam-se até ao dia 15 do mês seguinte, em linha através do <strong>eCDF</strong>. O pagamento acompanha a declaração; havendo crédito de IVA, pode pedir-se o reembolso.</p>
<h2>O IVA intracomunitário</h2>

<h3>Vendas a profissionais da UE (B2B)</h3>

<p>As entregas de bens e prestações de serviços a sujeitos passivos noutros países da UE estão <strong>isentas de IVA luxemburguês</strong>. O cliente autoliquida o IVA no seu país.</p>

<p><strong>Condições:</strong></p>
<ul>
    <li>O cliente deve ter um número de IVA intracomunitário válido</li>
    <li>Esse número deve constar da fatura</li>
    <li>A menção "Isenção de IVA - Artigo 43 parágrafo 1 k) da lei do IVA" deve aparecer</li>
</ul>

<h3>Vendas a particulares da UE (B2C)</h3>

<p>Um <strong>limiar único de 10 000 € por ano</strong> rege estas vendas. Abaixo, aplica o IVA luxemburguês; acima, o IVA do país do cliente, declarado através do balcão único <strong>OSS</strong> ou por registo em cada país.</p>

<p><strong>Atenção:</strong> este limiar é <strong>comum</strong> às vendas à distância de bens <em>e</em> aos serviços eletrónicos, de telecomunicações e audiovisuais — todos os países da UE em conjunto, excluindo o Luxemburgo. O que deve acompanhar é, portanto, o <strong>total</strong> das suas vendas B2C europeias, e não cada categoria isoladamente. A operação que ultrapassa o limiar já é tributável no país do cliente.</p>
<h2>O número de IVA intracomunitário</h2>

<p>O número de IVA luxemburguês tem o formato <strong>LU + 8 dígitos</strong> (ex: LU12345678).</p>

<p>Este número deve constar:</p>
<ul>
    <li>De todas as suas faturas</li>
    <li>Das suas declarações de IVA</li>
    <li>Das suas declarações de trocas de bens (DEB)</li>
</ul>

<h2>Recuperação do IVA</h2>

<p>Como sujeito passivo, pode <strong>deduzir o IVA</strong> pago nas suas compras profissionais. Para tal:</p>

<ul>
    <li>Deve possuir uma <strong>fatura conforme</strong></li>
    <li>A compra deve estar ligada à sua <strong>atividade profissional</strong></li>
    <li>O IVA deve estar <strong>corretamente mencionado</strong> na fatura</li>
</ul>

<h2>Conselhos práticos</h2>

<ol>
    <li><strong>Verifique sempre a taxa aplicável</strong> antes de faturar</li>
    <li><strong>Valide os números de IVA</strong> dos seus clientes da UE no site VIES</li>
    <li><strong>Conserve as suas faturas durante 10 anos</strong> para justificar as suas deduções</li>
    <li><strong>Utilize um software adaptado</strong> para evitar erros de cálculo</li>
    <li><strong>Antecipe as suas declarações</strong> para evitar penalizações por atraso</li>
</ol>

<h2>Casos particulares frequentes</h2>

<h3>Restauração: 3 % e 17 % na mesma conta</h3>
<p>As refeições servidas no local são tributadas a <strong>3 %</strong>, mas as <strong>bebidas alcoólicas</strong> mantêm-se a <strong>17 %</strong>. As duas taxas coexistem, portanto, na mesma conta, e a repartição deve ficar visível.</p>

<h3>Hotelaria: 3 % qualquer que seja a classificação</h3>
<p>Ao contrário de outros países, o Luxemburgo aplica uniformemente <strong>3 %</strong> a todas as dormidas, da pensão ao hotel de luxo. Os serviços acessórios (spa, restaurante interno) seguem o seu próprio regime.</p>

<h3>Livros digitais: 3 %</h3>
<p>Os e-books beneficiam da mesma taxa que os livros em papel, ou seja <strong>3 %</strong>. Em contrapartida, as assinaturas de streaming de vídeo ou música mantêm-se a <strong>17 %</strong>: são serviços digitais, não livros.</p>

<h3>Obras de renovação: a condição que toda a gente esquece</h3>
<p>As obras de renovação de uma habitação afeta a residência principal podem beneficiar da taxa super-reduzida de <strong>3 %</strong>, dentro dos limites e condições fixados por regulamento grão-ducal (idade do edifício, teto de benefício fiscal por habitação).</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ A autorização deve ser pedida ANTES das obras</p>
    <p class="text-red-700">O <strong>artigo 65bis da lei do IVA</strong> é explícito: quem executa estas obras «deve pedir à administração a autorização para a aplicação da taxa super-reduzida», e esse pedido «deve ser apresentado […] <strong>antes da realização das obras</strong>». Fazer a obra e depois reclamar os 3 % não funciona. Numa renovação, a diferença entre 3 % e 17 % conta-se em milhares de euros.</p>
</div>

<h2>Enganar-se na taxa: as consequências</h2>

<ul>
    <li><strong>Taxa demasiado baixa</strong>: correção pela AED, juros de mora e coima administrativa de <strong>250 € a 10 000 € por infração</strong> (art. 77 LIVA)</li>
    <li><strong>Incumprimento que privou o Estado de receita</strong>: coima de <strong>10 % a 50 % do IVA em causa</strong> — proporcional, logo sem limite máximo</li>
    <li><strong>Taxa demasiado alta</strong>: o cliente pode pedir o reembolso, e tem de regularizar junto da AED</li>
    <li><strong>Recusa de dedução do lado do cliente</strong>: se a taxa estiver manifestamente errada, o cliente pode ver recusada a recuperação</li>
    <li><strong>Fraude deliberada</strong>: multa penal de 25 000 € a dez vezes o montante do IVA, e prisão de um mês a cinco anos (art. 80 LIVA)</li>
</ul>

<h2>Perguntas frequentes</h2>

<h3>A taxa normal está mesmo em 17 % em 2026?</h3>
<p>Sim. A taxa tinha sido temporariamente reduzida para 16 % em 2023; voltou a <strong>17 %</strong> a 1 de janeiro de 2024 e não mudou desde então.</p>

<h3>Que taxa aplicar se a minha fatura atravessa uma mudança de taxa?</h3>
<p>Manda a data do <strong>facto gerador</strong> — ou seja, a data da entrega ou da execução da prestação, não a data de emissão da fatura nem a do pagamento.</p>

<h3>Como justificar uma taxa especial numa inspeção?</h3>
<p>Conserve o que liga a operação à categoria: a natureza exata do bem ou do serviço e, no caso das obras de renovação, <strong>a autorização obtida antes do início da obra</strong>. Sem ela, os 3 % são recusados mesmo que as obras fossem elegíveis quanto ao fundo.</p>

<h3>A minha atividade está isenta?</h3>
<p>As isenções são taxativamente enumeradas na lei e não se presumem. Na dúvida, esclareça com o seu contabilista antes de emitir a primeira fatura — uma isenção aplicada indevidamente dificilmente se corrige depois.</p>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Lei do IVA coordenada - taxas, anexos A e B, artigos 65bis, 77, 80</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu - Declarações de IVA e periodicidades</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Portal da fiscalidade indireta</a></li>
</ul>
<h2>Conclusão</h2>

<p>A gestão do IVA no Luxemburgo exige um bom conhecimento das taxas aplicáveis e das obrigações declarativas. Ao utilizar um software de faturação como o faktur.lu, beneficia de uma aplicação automática das taxas corretas e de faturas conformes às exigências legais.</p><div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/iva-intracomunitario-guia-para-empresas-luxemburguesas" class="text-primary-500 hover:text-primary-600 text-sm">IVA intracomunitário →</a></li><li><a href="/pt/blog/isencao-de-iva-no-luxemburgo-limiar-obrigacoes-e-passagem-ao-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">isenção de IVA →</a></li><li><a href="/pt/blog/como-faturar-para-o-estrangeiro-a-partir-do-luxemburgo" class="text-primary-500 hover:text-primary-600 text-sm">faturar para o estrangeiro →</a></li></ul></div>
PTBODY,

    ];
};

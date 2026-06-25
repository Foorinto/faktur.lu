<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Portuguese translations of the 5 scheduled June 2026 long-tail articles.
 */
class BlogScheduledJune2026PtSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->orderBy('id')->first()?->id ?? 1;

        foreach ($this->articles() as $article) {
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                $this->command?->info("Skip PT: {$article['slug']}");
                continue;
            }

            DB::table('blog_posts')->insert([
                'author_id' => $authorId,
                'category_id' => $article['category_id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'excerpt' => $article['excerpt'],
                'content' => $article['content'],
                'cover_image' => $article['cover_image'],
                'meta_title' => $article['meta_title'],
                'meta_description' => $article['meta_description'],
                'status' => 'published',
                'published_at' => $article['published_at'],
                'locale' => 'pt',
                'translation_key' => $article['translation_key'],
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command?->info("Created PT: {$article['slug']}");
        }
    }

    protected function articles(): array
    {
        return [
            [
                'category_id' => 2,
                'title' => "Auditoria fiscal AED Luxemburgo: como se preparar em 2026",
                'slug' => 'auditoria-fiscal-aed-luxemburgo-2026-preparacao',
                'translation_key' => 'controle-fiscal-aed-luxembourg-2026-preparation',
                'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
                'meta_title' => 'Auditoria fiscal AED Luxemburgo 2026: guia de preparação | faktur.lu',
                'meta_description' => "Tudo sobre auditorias fiscais da AED no Luxemburgo: preparação, FAIA, documentos necessários, processo típico. Guia prático 2026 para freelancers e PME.",
                'excerpt' => "Receber uma notificação de auditoria da AED preocupa todos os trabalhadores independentes no Luxemburgo. Eis o que preparar e como o faktur.lu o ajuda a passar a auditoria em 30 minutos em vez de 3 dias.",
                'published_at' => '2026-06-01 09:00:00',
                'content' => <<<HTML
<p class="lead">Receber uma notificação de auditoria da <strong>Administração do Registo, dos Domínios e do IVA (AED)</strong> preocupa todos os trabalhadores independentes e dirigentes de PME no Luxemburgo. No entanto, com uma boa preparação, uma auditoria fiscal decorre em algumas horas sem stress. Eis como se preparar em 2026.</p>

<h2>Quem é abrangido por uma auditoria AED?</h2>

<p>Qualquer empresa registada para IVA no Luxemburgo pode ser auditada pela AED. Na prática, a administração visa:</p>

<ul>
    <li><strong>Empresas em crescimento rápido</strong> com flutuações de volume de negócios atípicas</li>
    <li><strong>Setores de risco</strong> identificados pela AED (comércio, restauração, construção, consultoria)</li>
    <li><strong>Declarações de IVA atípicas</strong> (crédito de IVA recorrente, operações intra-UE importantes)</li>
    <li><strong>Auditorias aleatórias</strong>: entre 3% e 5% das empresas são auditadas anualmente por critérios estatísticos</li>
</ul>

<p>Uma auditoria pode abranger <strong>os últimos 5 anos</strong> (prazo de prescrição padrão, alargado a 10 anos em caso de suspeita de fraude).</p>

<h2>Os 3 tipos de auditoria AED</h2>

<h3>1. Auditoria documental</h3>

<p>A AED pede-lhe para enviar documentos por correio ou via eletrónica. Sem visita. É o tipo mais frequente e menos intrusivo.</p>

<h3>2. Auditoria presencial</h3>

<p>Um inspetor da AED desloca-se às suas instalações para verificar livros contabilísticos, faturas, comprovativos de despesas. Anunciada por correio com pré-aviso mínimo de 8 dias.</p>

<h3>3. Auditoria inopinada</h3>

<p>Rara, reservada para suspeitas de fraude grave. O inspetor apresenta-se sem aviso prévio. Tem o direito de pedir a presença do seu contabilista durante a auditoria.</p>

<h2>Documentos que a AED pode pedir</h2>

<p>A lista varia consoante a atividade, mas os seguintes documentos são sistematicamente verificados:</p>

<ul>
    <li><strong>Todas as faturas emitidas e recebidas</strong> do período auditado, com menções LIVA obrigatórias</li>
    <li><strong>O ficheiro FAIA</strong> (Ficheiro de Auditoria Informatizado da AED) na versão 2.01</li>
    <li><strong>O livro de receitas e despesas</strong> ou contabilidade completa segundo o regime</li>
    <li><strong>As declarações de IVA</strong> mensais ou trimestrais assinadas</li>
    <li><strong>Extratos bancários</strong> profissionais do período</li>
    <li><strong>Comprovativos das despesas deduzidas</strong> (notas de despesa, faturas de fornecedores)</li>
    <li><strong>Contratos cliente/fornecedor</strong> significativos</li>
    <li><strong>Prova da autoliquidação</strong> para operações B2B intra-UE (números de IVA validados via VIES)</li>
</ul>

<h2>FAIA: o ponto crítico</h2>

<p>O <strong>FAIA</strong> tornou-se o pivô de todas as auditorias AED desde 2020. Se não puder fornecer um FAIA conforme à versão 2.01, o inspetor considera a sua contabilidade não conforme aos padrões.</p>

<p>O FAIA é um ficheiro XML estruturado que agrupa:</p>

<ul>
    <li>Cabeçalhos da empresa (denominação, número de IVA, RCS, período)</li>
    <li>Todas as faturas de venda do período auditado</li>
    <li>Entradas contabilísticas associadas</li>
    <li>Totais débito/crédito por movimento</li>
</ul>

<p>Se faturar com Excel ou um sistema que não gere FAIA, terá de o reconstituir manualmente - o que pode demorar <strong>vários dias de trabalho</strong> e expor a erros.</p>

<h2>Como decorre uma auditoria presencial</h2>

<ol>
    <li><strong>Notificação escrita</strong> com pré-aviso mínimo de 8 dias (exceto auditoria inopinada)</li>
    <li><strong>Chegada dos inspetores AED</strong> com comissão de auditoria</li>
    <li><strong>Pedido de documentos</strong> segundo lista pré-estabelecida</li>
    <li><strong>Verificação das entradas</strong> e cruzamento com declarações de IVA</li>
    <li><strong>Entrevista</strong> sobre operações fora do padrão ou discrepâncias detetadas</li>
    <li><strong>Notificação das conclusões</strong> por correio em 60 dias</li>
</ol>

<p>Tem então <strong>30 dias para contestar</strong> as conclusões por reclamação ao diretor da AED.</p>

<h2>Os 5 erros que custam caro</h2>

<ol>
    <li><strong>Numeração não sequencial das faturas</strong> (Artigo 61 LIVA): falhas na sequência ou duplicações = presunção de omissão de volume de negócios</li>
    <li><strong>Menções LIVA em falta</strong> nas faturas: número de IVA, RCS, matrícula, data de emissão, etc.</li>
    <li><strong>Autoliquidação B2B intra-UE não documentada</strong>: sem validação VIES do número de IVA do cliente, a AED pode requalificar como operação tributável</li>
    <li><strong>Comprovativos de despesas em falta</strong> ou ilegíveis (nota manuscrita, recibo desbotado)</li>
    <li><strong>Inconsistências entre declaração de IVA e faturas</strong>: mesmo um pequeno desvio desencadeia verificação aprofundada</li>
</ol>

<h2>Preparar a auditoria em 5 etapas</h2>

<p>Seja freelancer ou PME, eis a checklist a executar a partir da notificação:</p>

<ol>
    <li><strong>Preparar o FAIA</strong> do período pedido - idealmente com um clique no seu software de faturação</li>
    <li><strong>Imprimir ou exportar</strong> todas as faturas emitidas (PDF/A para arquivo legal)</li>
    <li><strong>Verificar a coerência</strong> com as declarações de IVA submetidas</li>
    <li><strong>Constituir um dossiê</strong> por mês: faturas + extratos bancários + declaração de IVA correspondente</li>
    <li><strong>Avisar o seu contabilista</strong> e pedir-lhe que esteja presente (recomendado)</li>
</ol>

<h2>Como o faktur.lu o prepara</h2>

<p>Com uma plataforma de faturação conforme ao Luxemburgo, a auditoria AED prepara-se em <strong>30 minutos em vez de vários dias</strong>:</p>

<ul>
    <li>Numeração sequencial automática conforme ao <strong>Artigo 61 LIVA</strong></li>
    <li>Menções LIVA obrigatórias geradas automaticamente em cada fatura</li>
    <li>Validação VIES em tempo real para operações B2B intra-UE</li>
    <li>Exportação <strong>FAIA 2.01</strong> com um clique em qualquer período</li>
    <li>Arquivo <strong>PDF/A</strong> das faturas (norma legal 10 anos, art. 16 do Código Comercial)</li>
    <li>Portal do contabilista: o seu contabilista recupera tudo sem trocas de e-mails</li>
</ul>

<p><a href="/pt/register" class="text-primary-500 hover:underline font-medium">Comece gratuitamente com o faktur.lu</a> e prepare a sua próxima auditoria sem stress.</p>

<h2>FAQ - Auditoria fiscal AED</h2>

<h3>Quanto tempo dura uma auditoria AED?</h3>
<p>Auditoria documental: 2 a 4 semanas (consoante tempo de envio dos documentos). Auditoria presencial: tipicamente 1 a 3 dias na empresa + 30 a 60 dias de análise pela AED.</p>

<h3>Posso recusar uma auditoria?</h3>
<p>Não. A recusa de auditoria é sancionada por multa administrativa e presume má-fé. Pode no entanto pedir adiamento por motivo legítimo (doença, ausência prolongada) e exigir a presença do seu contabilista.</p>

<h3>Quais as multas em caso de erro?</h3>
<p>Variável: 10% a 50% do IVA não declarado em caso de omissão de boa-fé, até 200% em caso de fraude caracterizada. Multas fixas (50 EUR a 25.000 EUR) podem também aplicar-se a faltas formais (menções em falta, FAIA não fornecido, etc.).</p>

<h3>A auditoria pode abranger anos antigos?</h3>
<p>Sim: a prescrição é de 5 anos para faltas de boa-fé, alargada a 10 anos em caso de suspeita de fraude. É portanto crucial arquivar 10 anos (obrigação legal, art. 16 do Código Comercial luxemburguês).</p>

<h3>Que fazer se não concordar com as conclusões?</h3>
<p>Tem 30 dias para enviar reclamação escrita ao diretor da AED, expondo argumentos e juntando comprovativos. Em caso de recusa, recurso ao Tribunal Administrativo em 3 meses.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Prepare a sua próxima auditoria hoje</h3>
    <p class="text-primary-800 mb-4">Numeração automática, FAIA 2.01 com um clique, menções LIVA conformes, arquivo PDF/A 10 anos. A auditoria AED torna-se rotina.</p>
    <a href="/pt/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Comece grátis</a>
</div>
HTML,
            ],

            [
                'category_id' => 2,
                'title' => "FAIA Luxemburgo: guia completo do ficheiro de auditoria em 2026",
                'slug' => 'faia-luxemburgo-guia-completo-ficheiro-auditoria-2026',
                'translation_key' => 'faia-luxembourg-guide-fichier-audit-informatise-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&h=630&fit=crop',
                'meta_title' => 'FAIA Luxemburgo 2026: guia completo do ficheiro de auditoria AED | faktur.lu',
                'meta_description' => "FAIA explicado para freelancers e PME do Luxemburgo: o que é, quem é abrangido, como gerar um FAIA 2.01 conforme. Validador gratuito incluído.",
                'excerpt' => "O FAIA é o formato imposto pela AED nas suas auditorias fiscais. Eis tudo o que deve compreender em 2026: estrutura do ficheiro XML, quem é abrangido, como gerá-lo e como validá-lo gratuitamente.",
                'published_at' => '2026-06-08 09:00:00',
                'content' => <<<HTML
<p class="lead">O <strong>FAIA</strong> (Ficheiro de Auditoria Informatizado da AED) é o padrão exigido pela administração fiscal luxemburguesa durante toda auditoria de IVA. Contudo, poucos freelancers e PME sabem realmente o que contém. Este guia explica tudo em 2026.</p>

<h2>O que é o FAIA?</h2>

<p>O <strong>FAIA</strong> é um ficheiro XML estruturado, baseado na norma internacional <strong>SAF-T</strong> (Standard Audit File for Tax) desenvolvida pela OCDE. Agrupa todos os dados contabilísticos de uma empresa num determinado período, num formato legível automaticamente pelas ferramentas da AED.</p>

<p>A versão atualmente em vigor é a <strong>versão 2.01</strong>, publicada pela AED em 2020. O esquema XSD oficial está disponível no site da administração e faz fé em caso de litígio sobre conformidade.</p>

<h2>Quem deve fornecer um FAIA?</h2>

<p>Qualquer empresa registada para IVA no Luxemburgo <strong>deve poder produzir um FAIA conforme</strong> em caso de auditoria fiscal. Isto abrange:</p>

<ul>
    <li><strong>Freelancers e trabalhadores independentes</strong>, incluindo em regime de isenção de IVA</li>
    <li><strong>PME</strong> em qualquer regime (simplificado ou normal)</li>
    <li><strong>Sociedades comerciais</strong> (SARL, SA, SAS)</li>
    <li><strong>Associações</strong> com atividade económica sujeita a IVA</li>
</ul>

<p>A obrigação não é gerar o FAIA mensalmente - basta poder produzi-lo <strong>a pedido da AED</strong> num prazo razoável (tipicamente 15 dias após notificação).</p>

<h2>O que contém um FAIA?</h2>

<p>Um FAIA 2.01 está estruturado em várias secções obrigatórias:</p>

<h3>1. Cabeçalho (Header)</h3>

<ul>
    <li>Denominação, número de IVA, RCS, matrícula</li>
    <li>Endereço da empresa</li>
    <li>Período coberto (data de início e fim)</li>
    <li>Data de criação do ficheiro</li>
    <li>Moeda (EUR)</li>
</ul>

<h3>2. Plano de contas (MasterFiles)</h3>

<p>Lista das contas utilizadas (clientes, fornecedores, contas gerais). Para freelancer ou pequena estrutura, esta secção pode ser mínima.</p>

<h3>3. Faturas de venda (SalesInvoices)</h3>

<ul>
    <li>Número de fatura (sequencial, conforme Artigo 61 LIVA)</li>
    <li>Data de finalização</li>
    <li>Cliente: nome, número de IVA, endereço</li>
    <li>Linhas de fatura: descrição, quantidade, preço unitário, IVA</li>
    <li>Totais s/IVA, IVA, c/IVA</li>
</ul>

<h3>4. Movimentos (GeneralLedgerEntries)</h3>

<p>Entradas contabilísticas correspondentes (débito/crédito por conta).</p>

<h3>5. Rodapé (Footer)</h3>

<p>Totais de controlo: total debitado, total creditado, número de transações.</p>

<h2>Os 4 erros FAIA mais frequentes</h2>

<ol>
    <li><strong>Numeração de faturas não conforme</strong> ao Artigo 61 LIVA (falhas na sequência ou duplicações). O validador oficial rejeita o ficheiro.</li>
    <li><strong>Campos obrigatórios em falta</strong>: número de IVA cliente, endereço completo, menção de autoliquidação para B2B intra-UE.</li>
    <li><strong>Totais inconsistentes</strong> entre secções (ex: soma de faturas ≠ Total Sales Invoices declarado).</li>
    <li><strong>Formato de data incorreto</strong>: o FAIA exige formato ISO 8601 (YYYY-MM-DD), não DD/MM/YYYY.</li>
</ol>

<h2>Como gerar um FAIA conforme</h2>

<p>Três opções consoante a sua situação:</p>

<h3>Opção 1 - Software de faturação FAIA-nativo</h3>

<p>O método mais simples e seguro. Um software como o <a href="/pt" class="text-primary-500 hover:underline font-medium">faktur.lu</a> gera o FAIA 2.01 a pedido, em qualquer período, com um clique. O ficheiro é automaticamente conforme ao esquema XSD oficial.</p>

<h3>Opção 2 - Exportação a partir de software de contabilidade</h3>

<p>Sage BOB 50, Sage 100 e a maioria dos softwares de contabilidade profissionais permitem exportação FAIA. Verifique com o seu editor que a versão 2.01 é suportada.</p>

<h3>Opção 3 - Construção manual (desaconselhada)</h3>

<p>Teoricamente possível com um programador XML, mas propenso a erros. A reservar para casos extremos (dados legacy, migração).</p>

<h2>Validar gratuitamente o seu FAIA</h2>

<p>Antes de enviar o seu FAIA à AED, valide-o contra o esquema oficial para detetar erros antes do inspetor.</p>

<p>O faktur.lu oferece um <a href="/pt/validador-faia" class="text-primary-500 hover:underline font-medium">validador FAIA gratuito</a> que:</p>

<ul>
    <li>Verifica a conformidade XML ao esquema XSD AED 2.01</li>
    <li>Deteta campos obrigatórios em falta</li>
    <li>Verifica a coerência dos totais</li>
    <li>Verifica a sequencialidade dos números de fatura</li>
    <li>Não armazena dados (o ficheiro fica na sua máquina)</li>
</ul>

<h2>FAQ - FAIA</h2>

<h3>É preciso enviar um FAIA todos os anos?</h3>
<p>Não. O FAIA é produzido apenas <strong>a pedido da AED</strong> em caso de auditoria. Deve no entanto poder gerá-lo rapidamente (em 15 dias).</p>

<h3>O que acontece se não puder fornecer um FAIA?</h3>
<p>O inspetor considera a sua contabilidade não conforme aos padrões e pode aplicar uma <strong>multa administrativa</strong> ou estimar o seu volume de negócios em base forfetária (contra si). Na pior hipótese: liquidação adicional de IVA com agravamento.</p>

<h3>O FAIA é obrigatório em regime de isenção de IVA?</h3>
<p>Sim. Mesmo em isenção (Artigo 56 ter LIVA), deve poder produzir um FAIA se a AED o solicitar. O FAIA incluirá menções "IVA não aplicável" em cada fatura.</p>

<h3>Como saber se o meu FAIA é conforme antes de uma auditoria?</h3>
<p>Use um <a href="/pt/validador-faia" class="text-primary-500 hover:underline font-medium">validador FAIA</a> que o compara ao esquema XSD AED 2.01 oficial. Gratuito, sem registo, demora 5 segundos.</p>

<h3>O meu contabilista pode gerar o FAIA por mim?</h3>
<p>Sim, o seu contabilista pode gerar o FAIA a partir da sua própria ferramenta ou recuperar o seu via portal do contabilista. Com o faktur.lu, convida o seu contabilista em modo de leitura e ele acede ao FAIA com um clique.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">FAIA 2.01 com um clique, conforme AED</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera o seu FAIA em qualquer período, validado automaticamente contra o esquema XSD oficial. Incluído desde o plano Gratuito.</p>
    <a href="/pt/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Testar grátis</a>
</div>
HTML,
            ],

            [
                'category_id' => 3,
                'title' => "Artigo 21 LIVA: autoliquidação de IVA B2B intra-UE para freelancers luxemburgueses",
                'slug' => 'artigo-21-liva-autoliquidacao-iva-b2b-intra-ue-freelancers-luxemburgo',
                'translation_key' => 'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1559386484-97dfc0e15539?w=1200&h=630&fit=crop',
                'meta_title' => 'Artigo 21 LIVA: autoliquidação B2B intra-UE Luxemburgo | faktur.lu',
                'meta_description' => "Fatura um cliente B2B na Europa? O Artigo 21 LIVA impõe a autoliquidação. Eis as regras, menções obrigatórias, validação VIES e armadilhas em 2026.",
                'excerpt' => "É freelancer luxemburguês e fatura um cliente B2B em França, Bélgica ou Alemanha? O Artigo 21 LIVA aplica-se: autoliquidação do IVA. Eis as regras, menções obrigatórias e como evitar erros.",
                'published_at' => '2026-06-15 09:00:00',
                'content' => <<<HTML
<p class="lead">É freelancer luxemburguês e fatura um cliente B2B em França, Alemanha ou Bélgica? <strong>O Artigo 21 LIVA</strong> aplica-se: autoliquidação do IVA. Se esquecer a menção ou faturar o IVA luxemburguês por engano, arrisca uma liquidação adicional. Eis as regras claras.</p>

<h2>O Artigo 21 LIVA em resumo</h2>

<p>O <strong>Artigo 21 da lei luxemburguesa do IVA (LIVA)</strong> transpõe o princípio europeu do <strong>reverse charge</strong> para prestações de serviços entre sujeitos passivos em dois Estados-Membros da UE.</p>

<p>Concretamente, quando um freelancer luxemburguês fatura um serviço a uma empresa noutro país da UE:</p>

<ul>
    <li>O IVA luxemburguês <strong>não é devido</strong></li>
    <li>O cliente estrangeiro <strong>autoliquida o IVA</strong> no seu país à taxa local aplicável</li>
    <li>A fatura menciona explicitamente <strong>"Autoliquidation, article 21 LIVA"</strong></li>
</ul>

<h2>Quando se aplica o Artigo 21 LIVA?</h2>

<p>Três condições cumulativas devem estar reunidas:</p>

<ol>
    <li><strong>Prestação de serviços</strong> (não venda de bens - regras diferentes)</li>
    <li><strong>Cliente profissional (B2B)</strong> noutro Estado-Membro da UE</li>
    <li><strong>Número de IVA intracomunitário válido</strong> do cliente (validação VIES obrigatória)</li>
</ol>

<p>Se uma condição faltar, a regra muda:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Situação</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Regra IVA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente B2B intra-UE com IVA válido</td><td class="border border-gray-300 px-4 py-2"><strong>Autoliquidação (Art. 21 LIVA)</strong></td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente B2C (particular) intra-UE</td><td class="border border-gray-300 px-4 py-2">IVA luxemburguês 17% (ou OSS se limite ultrapassado)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B intra-UE sem IVA validado</td><td class="border border-gray-300 px-4 py-2">IVA luxemburguês 17%</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente fora da UE (B2B ou B2C)</td><td class="border border-gray-300 px-4 py-2">Isenção com menção adaptada</td></tr>
    </tbody>
</table>

<h2>A validação VIES, etapa crítica</h2>

<p>VIES (VAT Information Exchange System) é o serviço europeu que permite verificar em tempo real a validade de um número de IVA intracomunitário.</p>

<p>Antes de emitir uma fatura em autoliquidação, <strong>deve imperativamente</strong> validar o número de IVA do cliente via VIES. Se o número não for válido à data da fatura, a AED considera a prestação tributável no Luxemburgo e liquida o IVA não cobrado.</p>

<p>O serviço VIES oficial está acessível em <a href="https://ec.europa.eu/taxation_customs/vies/" rel="external nofollow" target="_blank" class="text-primary-500 hover:underline">ec.europa.eu/taxation_customs/vies</a>. Mas na prática, o seu software de faturação deve fazê-lo automaticamente em cada fatura.</p>

<h2>Menções obrigatórias na fatura</h2>

<p>Uma fatura em autoliquidação Artigo 21 LIVA deve conter, além das menções habituais:</p>

<ul>
    <li>O seu <strong>número de IVA luxemburguês</strong> (LU + 8 dígitos)</li>
    <li>O <strong>número de IVA do cliente</strong> (prefixo país + dígitos)</li>
    <li>A menção exata: <strong>"Autoliquidation, article 21 LIVA"</strong> (ou equivalente na língua do cliente)</li>
    <li><strong>Sem IVA acrescentado</strong> ao total (IVA = 0 EUR)</li>
    <li>O total s/IVA torna-se também o total c/IVA</li>
</ul>

<p><strong>Importante</strong>: a menção "autoliquidação" deve ser visível e compreensível. A AED já sancionou empresas que usaram formulações vagas como "IVA não aplicável" sem precisão.</p>

<h2>Exemplo concreto</h2>

<p>A Maria é designer UX freelancer no Luxemburgo. Fatura <strong>2.500 EUR s/IVA</strong> à <strong>Acme SA</strong>, agência parisiense (n.º IVA FR12345678901, validado via VIES).</p>

<p>A sua fatura contém:</p>

<ul>
    <li><strong>Emissor</strong>: Maria Dupont, LU12345678</li>
    <li><strong>Destinatário</strong>: Acme SA, 10 rue de Rivoli 75001 Paris, FR12345678901</li>
    <li><strong>Serviço</strong>: Design UX site web - 2.500,00 EUR</li>
    <li><strong>IVA (0%)</strong>: 0,00 EUR</li>
    <li><strong>Total c/IVA</strong>: 2.500,00 EUR</li>
    <li><strong>Menção</strong>: <em>"Autoliquidation, article 21 LIVA - IVA devido pelo destinatário no seu país."</em></li>
</ul>

<p>A Acme SA declara o IVA francês (20%) na sua declaração de IVA, como IVA cobrado e como IVA dedutível (operação neutra para eles).</p>

<h2>Os 3 erros que custam caro</h2>

<ol>
    <li><strong>Esquecer a validação VIES</strong>: se o n.º de IVA se revelar inválido ou suspenso, deve cobrar o IVA luxemburguês (17%). Sem o cliente para pagar, sai do seu bolso.</li>
    <li><strong>Menção "autoliquidação" em falta ou vaga</strong>: a AED requalifica como operação tributável no Luxemburgo.</li>
    <li><strong>Tratamento contabilístico incorreto</strong>: a operação deve aparecer na declaração de IVA luxemburguesa (rubrica serviços intracomunitários) e na <strong>declaração recapitulativa VIES</strong> (mensal ou trimestral).</li>
</ol>

<h2>Como o faktur.lu o protege</h2>

<p>O faktur.lu deteta automaticamente as condições de autoliquidação e aplica o Artigo 21 LIVA:</p>

<ul>
    <li><strong>Validação VIES em tempo real</strong> assim que insere um cliente B2B intra-UE</li>
    <li>Menção <strong>"Autoliquidation, article 21 LIVA"</strong> adicionada automaticamente à fatura</li>
    <li><strong>IVA forçado a 0</strong> para serviços abrangidos, cálculos verificados</li>
    <li><strong>Declaração recapitulativa VIES</strong> gerada automaticamente no final do período</li>
    <li>Menções traduzidas para a língua do cliente (FR, DE, EN, PT)</li>
</ul>

<p>Evita erros e fatura clientes estrangeiros em total conformidade.</p>

<h2>FAQ - Artigo 21 LIVA</h2>

<h3>E se o meu cliente não tiver número de IVA?</h3>
<p>Sem número de IVA validado via VIES, a autoliquidação não se aplica. Deve faturar o IVA luxemburguês (17% padrão). O mesmo para particulares (B2C).</p>

<h3>E quanto a entregas de bens (não serviços)?</h3>
<p>Para vendas de bens B2B intra-UE, aplica-se o <strong>Artigo 43 LIVA</strong> (entregas intracomunitárias isentas). As regras e menções são diferentes.</p>

<h3>É preciso declarar a operação à AED?</h3>
<p>Sim. A operação aparece em:</p>
<ul>
    <li>A sua <strong>declaração de IVA</strong> luxemburguesa (campo prestações de serviços intra-UE)</li>
    <li>A <strong>declaração recapitulativa VIES</strong> (mensal se volume de negócios > 50.000 EUR em 12 meses, caso contrário trimestral)</li>
</ul>

<h3>O cliente pode recusar a autoliquidação?</h3>
<p>Não, é uma regra europeia obrigatória. Pode no entanto recusar faturar se o cliente não tiver um n.º de IVA validado (ou faturar em IVA luxemburguês padrão).</p>

<h3>O que acontece numa auditoria AED?</h3>
<p>A AED verifica a <strong>prova de validação VIES</strong> à data da fatura (um screenshot ou log automático serve). Sem esta prova, a operação é requalificada como tributável e deve IVA + penalidades.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">A autoliquidação, sem risco de erro</h3>
    <p class="text-primary-800 mb-4">O faktur.lu valida VIES em tempo real, aplica o Artigo 21 LIVA automaticamente, gera as suas declarações recapitulativas. Sem stress com clientes europeus.</p>
    <a href="/pt/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Começar grátis</a>
</div>
HTML,
            ],

            [
                'category_id' => 1,
                'title' => "IVA Luxemburgo 2026: as 4 taxas (17%, 14%, 8%, 3%) explicadas",
                'slug' => 'iva-luxemburgo-2026-quatro-taxas-17-14-8-3-explicadas',
                'translation_key' => 'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques',
                'cover_image' => 'https://images.unsplash.com/photo-1543286386-713bdd548da4?w=1200&h=630&fit=crop',
                'meta_title' => 'IVA Luxemburgo 2026: guia das 4 taxas (17%, 14%, 8%, 3%) | faktur.lu',
                'meta_description' => "Qual taxa de IVA aplicar no Luxemburgo em 2026? Padrão 17%, intermediária 14%, reduzida 8%, super-reduzida 3%. Guia prático com exemplos concretos por setor.",
                'excerpt' => "O Luxemburgo aplica 4 taxas de IVA. Qual usar para quê? Eis o guia prático 2026 com exemplos concretos por taxa: serviços, alimentação, livros, restauração, gás, eletricidade.",
                'published_at' => '2026-06-22 09:00:00',
                'content' => <<<HTML
<p class="lead">O Luxemburgo aplica <strong>4 taxas de IVA diferentes</strong> em 2026: 17% (padrão), 14% (intermediária), 8% (reduzida) e 3% (super-reduzida). Escolher a taxa certa é crucial para a conformidade fiscal. Eis o guia completo 2026 com exemplos concretos.</p>

<h2>As 4 taxas de IVA em vigor em 2026</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Taxa</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Designação</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Aplicação</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td><td class="border border-gray-300 px-4 py-2">Padrão</td><td class="border border-gray-300 px-4 py-2">Maioria dos bens e serviços</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td><td class="border border-gray-300 px-4 py-2">Intermediária</td><td class="border border-gray-300 px-4 py-2">Vinhos, certos combustíveis</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td><td class="border border-gray-300 px-4 py-2">Reduzida</td><td class="border border-gray-300 px-4 py-2">Gás, eletricidade, cabeleireiro, certas obras</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td><td class="border border-gray-300 px-4 py-2">Super-reduzida</td><td class="border border-gray-300 px-4 py-2">Alimentação, livros, medicamentos, transportes públicos</td></tr>
    </tbody>
</table>

<p>Estas taxas estão em vigor desde <strong>1 de janeiro de 2024</strong> (a taxa padrão regressou de 16% a 17% nesta data após a redução temporária de 2023).</p>

<h2>Taxa padrão 17%: por defeito</h2>

<p>A taxa padrão aplica-se a <strong>todas as operações tributáveis</strong> não cobertas explicitamente por outra taxa. Concretamente:</p>

<ul>
    <li><strong>Prestações de serviços</strong>: consultoria, design, desenvolvimento, marketing, formação, etc.</li>
    <li><strong>Vendas de bens não alimentares</strong>: material informático, mobiliário, vestuário, etc.</li>
    <li><strong>Aluguer de material profissional</strong></li>
    <li><strong>Alojamento hoteleiro</strong> (4 estrelas ou mais)</li>
    <li><strong>Restaurantes</strong> (bebidas alcoólicas)</li>
</ul>

<p>É <strong>a taxa por defeito</strong> que deve aplicar em caso de dúvida, enquanto clarifica a situação com o seu contabilista.</p>

<h2>Taxa intermediária 14%: vinhos e combustíveis</h2>

<p>A taxa intermediária abrange principalmente:</p>

<ul>
    <li><strong>Vinhos</strong> (tranquilos e espumantes, exceto champanhe)</li>
    <li><strong>Certos combustíveis sólidos</strong> (carvão, lenha)</li>
    <li><strong>Algumas publicações publicitárias</strong></li>
</ul>

<p>Esta taxa é rara. Se não está no setor do vinho ou aquecimento, provavelmente nunca a usará.</p>

<h2>Taxa reduzida 8%: energia e serviços pessoais</h2>

<p>A taxa reduzida aplica-se a vários setores:</p>

<ul>
    <li><strong>Fornecimento de gás e eletricidade</strong></li>
    <li><strong>Cabeleireiro</strong></li>
    <li><strong>Restauração</strong> (exceto bebidas alcoólicas)</li>
    <li><strong>Alojamento hoteleiro</strong> (1 a 3 estrelas)</li>
    <li><strong>Obras de renovação</strong> de habitações antigas (sob condições)</li>
    <li><strong>Espetáculos ao vivo</strong> (teatro, concertos)</li>
    <li><strong>Sapateiro, reparação de bicicletas, alteração de roupa</strong></li>
</ul>

<h2>Taxa super-reduzida 3%: produtos essenciais</h2>

<p>A taxa mais baixa cobre os produtos considerados essenciais:</p>

<ul>
    <li><strong>Produtos alimentares</strong> de base (pão, leite, carne, vegetais, fruta)</li>
    <li><strong>Livros, jornais, periódicos</strong> (papel e digital desde 2020)</li>
    <li><strong>Medicamentos</strong> (com ou sem prescrição)</li>
    <li><strong>Transportes públicos de pessoas</strong> (autocarro, comboio, elétrico)</li>
    <li><strong>Produção agrícola</strong> (sementes, fertilizantes)</li>
    <li><strong>Água distribuída por redes públicas</strong></li>
    <li><strong>Obras de arte</strong> (sob certas condições)</li>
</ul>

<h2>Casos particulares frequentes</h2>

<h3>Restauração: 8% ou 17%?</h3>

<p>As <strong>refeições servidas no local</strong> num restaurante são a <strong>8%</strong>, mas as <strong>bebidas alcoólicas</strong> permanecem a <strong>17%</strong>. Uma garrafa de vinho servida com uma refeição é faturada à parte.</p>

<h3>Hotelaria: 8% ou 17%?</h3>

<p>O alojamento em hotel de 1 a 3 estrelas é a <strong>8%</strong>. A partir de 4 estrelas, é <strong>17%</strong>. Os serviços anexos (spa, room service) seguem o seu próprio regime.</p>

<h3>E-books: 3% ou 17%?</h3>

<p>Desde 2020, os <strong>livros digitais (e-books)</strong> beneficiam da mesma taxa que os livros em papel, ou seja, <strong>3%</strong>. Mas as subscrições a plataformas de streaming (Netflix, Spotify) permanecem a 17%.</p>

<h3>Obras de renovação: 8% ou 17%?</h3>

<p>As obras de renovação de habitação com <strong>mais de 2 anos</strong> beneficiam da taxa reduzida de <strong>8%</strong>, sob condição de materiais e mão-de-obra serem faturados em conjunto pela empresa de construção. Para habitação nova: 17%.</p>

<h2>Como calcular o IVA luxemburguês</h2>

<p>Se faturar 1.000 EUR s/IVA a um cliente luxemburguês à taxa padrão 17%:</p>

<ul>
    <li><strong>S/IVA</strong>: 1.000,00 EUR</li>
    <li><strong>IVA (17%)</strong>: 170,00 EUR</li>
    <li><strong>C/IVA</strong>: 1.170,00 EUR</li>
</ul>

<p>Inversamente, se conhece o c/IVA e procura o s/IVA:</p>

<ul>
    <li><strong>S/IVA</strong> = C/IVA ÷ (1 + taxa/100)</li>
    <li><strong>Exemplo</strong>: 1.170 EUR c/IVA ÷ 1,17 = 1.000 EUR s/IVA</li>
</ul>

<p>Para poupar tempo, use a nossa <a href="/pt/ferramentas/calculadora-iva" class="text-primary-500 hover:underline font-medium">calculadora de IVA Luxemburgo gratuita</a> que suporta as 4 taxas.</p>

<h2>Erros de taxa: as consequências</h2>

<p>Aplicar a taxa errada não é trivial:</p>

<ul>
    <li><strong>Subfaturação de IVA</strong> (taxa demasiado baixa): liquidação adicional pela AED + juros + penalidades (10% a 50%)</li>
    <li><strong>Sobrefaturação de IVA</strong> (taxa demasiado alta): o cliente pode pedir reembolso, e deve regularizar com a AED</li>
    <li><strong>Recusa de dedução pelo cliente</strong>: se a taxa for claramente errada, o cliente pode recusar deduzir o IVA</li>
</ul>

<h2>Como o faktur.lu evita os erros</h2>

<p>O faktur.lu aplica automaticamente a taxa certa segundo a natureza da prestação:</p>

<ul>
    <li>Seleção de taxa por <strong>tipo de prestação</strong> na criação do produto/serviço</li>
    <li>Deteção automática dos casos <strong>B2B intra-UE</strong> (autoliquidação art. 21 LIVA)</li>
    <li>Aplicação da <strong>taxa 0</strong> em caso de isenção (art. 56 ter)</li>
    <li>Menções LIVA obrigatórias geradas em cada fatura</li>
</ul>

<h2>FAQ - IVA Luxemburgo</h2>

<h3>A taxa padrão é realmente 17% em 2026?</h3>
<p>Sim, desde 1 de janeiro de 2024. A taxa esteve temporariamente em 16% em 2023 para apoio ao poder de compra, depois voltou a 17% que é a taxa histórica.</p>

<h3>A minha atividade está isenta de IVA?</h3>
<p>Certas atividades estão isentas (saúde, educação, seguros, arrendamento residencial), mas isso significa também que não pode <strong>recuperar o IVA</strong> nas suas compras profissionais. Verifique o seu caso com o contabilista.</p>

<h3>Quando mudar de taxa numa fatura em curso?</h3>
<p>Se a taxa mudar entre a emissão do orçamento e a entrega do serviço, aplica-se a taxa <strong>em vigor à data de finalização da fatura</strong> (não a data do orçamento).</p>

<h3>E para vendas B2C transfronteiriças?</h3>
<p>Desde 2021, aplica-se o regime <strong>OSS (One Stop Shop)</strong>: acima de 10.000 EUR de vendas B2C intra-UE por ano, deve aplicar a taxa do país de destino. Caso contrário, pode aplicar o IVA luxemburguês.</p>

<h3>Como justificar uma taxa específica numa auditoria?</h3>
<p>Conserve os <strong>comprovativos da natureza da prestação</strong>: guias de entrega, contratos, fichas técnicas. A AED pode pedir para ver a nomenclatura ou composição de um produto para validar a taxa aplicada.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Calcular o IVA luxemburguês em 5 segundos</h3>
    <p class="text-primary-800 mb-4">A nossa calculadora de IVA gratuita suporta as 4 taxas luxemburguesas (17%, 14%, 8%, 3%). Sem registo, sem cartão.</p>
    <a href="/pt/ferramentas/calculadora-iva" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Abrir a calculadora</a>
</div>
HTML,
            ],

            [
                'category_id' => 3,
                'title' => "Artigo 61 LIVA: porque a numeração sequencial da sua fatura é obrigatória",
                'slug' => 'artigo-61-liva-numeracao-sequencial-faturas-luxemburgo-obrigatoria',
                'translation_key' => 'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire',
                'cover_image' => 'https://images.unsplash.com/photo-1568667256549-094345857637?w=1200&h=630&fit=crop',
                'meta_title' => 'Artigo 61 LIVA Luxemburgo: numeração sequencial obrigatória | faktur.lu',
                'meta_description' => "O Artigo 61 LIVA impõe numeração contínua das suas faturas no Luxemburgo. Falhas, duplicações, formatos: o que saber e como automatizar.",
                'excerpt' => "As suas faturas no Luxemburgo devem ter um número único, sequencial e contínuo. É o Artigo 61 LIVA. Uma falha na sequência ou uma duplicação pode desencadear uma liquidação adicional. Eis como cumprir sem pensar nisso.",
                'published_at' => '2026-06-29 09:00:00',
                'content' => <<<HTML
<p class="lead">O <strong>Artigo 61 LIVA</strong> impõe uma regra simples mas fundamental: as suas faturas luxemburguesas devem ter um <strong>número único, sequencial e contínuo</strong>. Uma falha na sequência ou uma duplicação pode desencadear uma liquidação adicional fiscal. Eis a regra explicada e como nunca mais pensar nisso.</p>

<h2>O que diz exatamente o Artigo 61 LIVA?</h2>

<p>O artigo 61 da lei luxemburguesa do IVA precisa que toda fatura emitida por um sujeito passivo deve conter um <strong>número sequencial</strong>, atribuído de forma <strong>cronológica e contínua</strong> por cada exercício fiscal.</p>

<p>Três regras cumulativas:</p>

<ol>
    <li><strong>Unicidade</strong>: duas faturas não podem ter o mesmo número</li>
    <li><strong>Sequencialidade</strong>: os números seguem por ordem (1, 2, 3, 4...)</li>
    <li><strong>Continuidade</strong>: sem falhas (sem passar de 5 a 8 sem 6 e 7)</li>
</ol>

<p>Esta obrigação aplica-se a <strong>todos os sujeitos passivos</strong> de IVA no Luxemburgo, incluindo em isenção (Artigo 56 ter), mesmo que a fatura tenha a menção "IVA não aplicável".</p>

<h2>Porque existe esta regra</h2>

<p>O objetivo é simples: <strong>impedir a dissimulação de receitas</strong>. Sem numeração contínua, uma empresa poderia "esquecer" facilmente certas faturas na declaração de IVA. A sequencialidade permite à AED verificar de relance se todas as faturas emitidas foram declaradas.</p>

<p>É também por isso que o <strong>FAIA</strong> inclui sistematicamente a lista de números de fatura do período auditado. Uma falha detetada no FAIA desencadeia imediatamente um pedido de explicação.</p>

<h2>Os formatos aceites</h2>

<p>O Artigo 61 não impõe um formato único. Pode usar qualquer convenção, desde que a sequencialidade seja respeitada:</p>

<ul>
    <li><strong>Numeração simples</strong>: 1, 2, 3, 4...</li>
    <li><strong>Com prefixo</strong>: F-001, F-002, F-003...</li>
    <li><strong>Com ano</strong>: 2026-001, 2026-002, 2026-003...</li>
    <li><strong>Com prefixo + ano</strong>: F-2026-001, F-2026-002...</li>
    <li><strong>Com cliente</strong> (desaconselhado): ACME-001, ACME-002 (quebra a continuidade global)</li>
</ul>

<p><strong>Dica</strong>: um número com <strong>ano + contador sequencial</strong> (ex: F-2026-001) é o mais legível e permite reiniciar a 1 todos os anos. É a prática mais comum no Luxemburgo.</p>

<h2>Reset anual: permitido ou não?</h2>

<p>Sim, pode reiniciar a numeração a <strong>1 todos os anos fiscais</strong>. É mesmo a prática mais corrente. O importante é que <strong>dentro do mesmo ano</strong>, a sequência seja contínua.</p>

<p>Exemplo válido:</p>

<ul>
    <li>F-2025-148 (última fatura de 2025)</li>
    <li>F-2026-001 (primeira fatura de 2026)</li>
    <li>F-2026-002</li>
    <li>F-2026-003</li>
</ul>

<p>A passagem de 148 a 001 é permitida porque muda o ano. Mas dentro de 2026, não pode passar de F-2026-001 a F-2026-003 sem ter emitido F-2026-002.</p>

<h2>O que acontece em caso de erro?</h2>

<h3>Caso 1 - Emitiu uma duplicação</h3>

<p>A AED considera uma das duas faturas como <strong>fictícia ou fraudulenta</strong>. Deve:</p>

<ol>
    <li>Emitir uma <strong>nota de crédito</strong> sobre uma das duas (anulação contabilística)</li>
    <li>Emitir uma <strong>nova fatura</strong> com o próximo número disponível</li>
    <li>Conservar os 3 documentos (as 2 faturas iniciais + a nota de crédito)</li>
</ol>

<h3>Caso 2 - Tem uma falha na sequência</h3>

<p>Caso mais grave. Deve poder <strong>explicar a falha</strong> à AED. Três explicações aceitáveis:</p>

<ul>
    <li>A fatura foi emitida depois <strong>anulada por nota de crédito</strong> (conserva ambos os documentos)</li>
    <li>Erro técnico: criou um rascunho não finalizado. Neste caso, melhor finalizá-lo e arquivá-lo, mesmo se a prestação não ocorreu (com menção "fatura anulada")</li>
    <li>Bug informático documentado (raro)</li>
</ul>

<p>Sem explicação credível, a AED presume dissimulação e pode <strong>estimar forfetariamente</strong> o volume de negócios em falta.</p>

<h3>Caso 3 - Quer apagar uma fatura já finalizada</h3>

<p>Legalmente impossível. Uma fatura finalizada deve permanecer arquivada 10 anos (artigo 16 do Código Comercial). Para anular:</p>

<ul>
    <li>Emitir uma <strong>nota de crédito</strong> do mesmo montante (numeração separada tipicamente: NC-2026-001)</li>
    <li>Conservar os 2 documentos (fatura + nota de crédito)</li>
</ul>

<h2>As armadilhas clássicas a evitar</h2>

<ol>
    <li><strong>Numeração manual Excel</strong>: risco muito alto de duplicações ou falhas (esquecer a última fatura, fórmula quebrada, copy-paste falhado)</li>
    <li><strong>Várias séries em paralelo</strong> (uma por cliente ou projeto): difícil de justificar em caso de auditoria</li>
    <li><strong>Reset numérico a meio do ano</strong> sem mudança de exercício fiscal: proibido</li>
    <li><strong>Apagar faturas finalizadas</strong> sem nota de crédito de substituição: violação do artigo 16 do Código Comercial + Artigo 61 LIVA</li>
</ol>

<h2>Como automatizar sem pensar nisso</h2>

<p>Um software de faturação como o <a href="/pt" class="text-primary-500 hover:underline font-medium">faktur.lu</a> gere a numeração por si:</p>

<ul>
    <li><strong>Numeração sequencial automática</strong>: impossível criar uma duplicação</li>
    <li><strong>Sem falhas possíveis</strong>: a cada finalização, o contador incrementa</li>
    <li><strong>Formato personalizável</strong>: prefixo, ano, comprimento do contador, tudo configurável</li>
    <li><strong>Reset anual automático</strong>: contador a 1 a cada 1 de janeiro (ou não, conforme preferência)</li>
    <li><strong>Sequências separadas</strong> para faturas, notas de crédito e orçamentos (contadores independentes mas cada um contínuo)</li>
    <li><strong>Verificação de unicidade</strong> em cada criação</li>
</ul>

<p>Com isto, o Artigo 61 LIVA já não é uma preocupação: a sua numeração é conforme por construção.</p>

<h2>FAQ - Artigo 61 LIVA</h2>

<h3>E se eu fatura em isenção de IVA (artigo 56 ter)?</h3>
<p>O Artigo 61 LIVA aplica-se <strong>na mesma</strong>. Toda fatura emitida, mesmo sem IVA cobrado, deve ter um número sequencial e contínuo.</p>

<h3>Posso ter uma numeração por cliente (ACME-001, ACME-002...)?</h3>
<p>Tecnicamente, sim - mas é fortemente desaconselhado. Em auditoria, a AED pede a sequencialidade <strong>global</strong>. Terá então de provar que não há falhas somando todas as séries de clientes, o que é complexo e arriscado.</p>

<h3>Os orçamentos estão abrangidos?</h3>
<p>Não, o Artigo 61 LIVA só diz respeito a <strong>faturas e notas de crédito</strong>. Pode numerar os seus orçamentos como quiser (numeração sequencial recomendada para o seu próprio acompanhamento).</p>

<h3>Posso ter séries separadas para faturas e notas de crédito?</h3>
<p>Sim, é mesmo recomendado. Uma série para faturas (F-2026-001, F-2026-002...) e uma série para notas de crédito (NC-2026-001, NC-2026-002...). Cada uma deve ser contínua, mas são independentes.</p>

<h3>O que fazer se migrar para um novo software a meio do ano?</h3>
<p>Deve <strong>continuar a sequência</strong> no novo software. Se estava em F-2026-148 no antigo, a sua primeira fatura no novo deve ser F-2026-149. O faktur.lu permite configurar o contador de partida na inscrição para evitar a falha.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Uma numeração conforme, sem pensar nisso</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gere automaticamente a sua numeração sequencial, o Artigo 61 LIVA é respeitado por construção. Formato personalizável, reset anual configurável.</p>
    <a href="/pt/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Começar grátis</a>
</div>
HTML,
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogPostsPortugueseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resoudre dynamiquement l'author_id : garder celui des posts FR existants
        // si ils existent (coherence multilingue), sinon premier user dispo, sinon NULL.
        $resolvedAuthorId = $this->resolveAuthorId();

        $articles = $this->getArticles();

        $count = 0;
        foreach ($articles as $article) {
            // Override l'author_id en dur (1) par celui resolu dynamiquement
            $article['author_id'] = $resolvedAuthorId;

            // Idempotent: identifié par locale + translation_key
            BlogPost::updateOrCreate(
                [
                    'locale' => 'pt',
                    'translation_key' => $article['translation_key'],
                ],
                $article
            );
            $count++;
        }

        $authorInfo = $resolvedAuthorId === null ? 'NULL' : "user_id={$resolvedAuthorId}";
        $this->command->info("Created/updated {$count} Portuguese (pt-PT) blog posts (author: {$authorInfo}).");
    }

    /**
     * Resolution de l'author_id pour eviter les violations de cle etrangere
     * (ex: prod n'a pas forcement user_id=1).
     */
    private function resolveAuthorId(): ?int
    {
        // 1. Reprendre l'author d'un post FR existant pour les memes translation_key
        $translationKeys = collect($this->getArticles())->pluck('translation_key')->all();
        $fromExistingFr = DB::table('blog_posts')
            ->where('locale', 'fr')
            ->whereIn('translation_key', $translationKeys)
            ->whereNotNull('author_id')
            ->value('author_id');

        if ($fromExistingFr) {
            return (int) $fromExistingFr;
        }

        // 2. Premier user disponible
        $firstUser = User::orderBy('id')->value('id');
        if ($firstUser) {
            return (int) $firstUser;
        }

        // 3. Aucun user en DB → NULL (la colonne est nullable, FK ON DELETE SET NULL)
        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getArticles(): array
    {
        return [
            $this->article1(),
            $this->article2(),
            $this->article3(),
            $this->article4(),
            $this->article5(),
            $this->article6(),
            $this->article7(),
            $this->article8(),
            $this->article9(),
            $this->article37(),
            $this->article38(),
            $this->article39(),
            $this->article40(),
            $this->article41(),
            $this->article42(),
            $this->article43(),
            $this->article44(),
            $this->article45(),
            $this->article46(),
            $this->article47(),
            $this->article48(),
            $this->article49(),
            $this->article50(),
            $this->article51(),
        ];
    }

    private function base(string $translationKey, int $categoryId, string $publishedAt, ?string $coverImage, ?int $authorId, string $title, string $excerpt, string $metaTitle, string $metaDescription, string $content): array
    {
        return [
            'translation_key' => $translationKey,
            'category_id' => $categoryId,
            'author_id' => $authorId,
            'status' => 'published',
            'published_at' => $publishedAt,
            'cover_image' => $coverImage,
            'locale' => 'pt',
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $excerpt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'content' => $content,
        ];
    }

    private function article1(): array
    {
        $title = 'Guia completo da faturação no Luxemburgo em 2026';
        $excerpt = 'Descubra todas as regras de faturação no Luxemburgo: menções obrigatórias, numeração, IVA, conservação dos documentos. O guia de referência para empresas e freelancers.';
        $metaTitle = 'Faturação Luxemburgo 2026: Guia Completo das Regras e Obrigações';
        $metaDescription = 'Guia completo sobre a faturação no Luxemburgo em 2026. Menções obrigatórias, IVA, numeração, FAIA: tudo o que precisa de saber para faturar em conformidade.';
        $content = <<<'HTML'
<p class="lead">A faturação no Luxemburgo obedece a regras precisas definidas pela legislação fiscal. Quer seja uma PME, um freelancer ou uma grande empresa, este guia explica-lhe tudo o que precisa de saber para faturar em conformidade.</p>

<h2>Porque é que a conformidade das suas faturas é essencial</h2>

<p>No Luxemburgo, uma fatura não é um simples documento comercial. É um <strong>documento contabilístico oficial</strong> que serve de base para:</p>

<ul>
    <li>O cálculo e a recuperação do IVA</li>
    <li>Os controlos fiscais da Administração das Contribuições Diretas (ACD)</li>
    <li>A geração do ficheiro FAIA para a Administração do Registo e dos Domínios (AED)</li>
    <li>A prova das suas transações comerciais</li>
</ul>

<p>Uma fatura não conforme pode levar à <strong>rejeição da dedução do IVA</strong> pelo seu cliente e a <strong>sanções financeiras</strong> para a sua empresa.</p>

<h2>As menções obrigatórias numa fatura luxemburguesa</h2>

<p>Segundo o artigo 63 da lei do IVA luxemburguesa, qualquer fatura deve conter as seguintes informações:</p>

<h3>Informações sobre o emissor</h3>

<ul>
    <li><strong>Nome ou denominação social</strong> da sua empresa</li>
    <li><strong>Endereço completo</strong> da sede social</li>
    <li><strong>Número de IVA intracomunitário</strong> (formato LU + 8 dígitos)</li>
    <li><strong>Número de autorização de estabelecimento</strong> (se aplicável)</li>
</ul>

<h3>Informações sobre o cliente</h3>

<ul>
    <li><strong>Nome ou denominação social</strong> do cliente</li>
    <li><strong>Endereço completo</strong></li>
    <li><strong>Número de IVA</strong> (obrigatório para as transações B2B intracomunitárias)</li>
</ul>

<h3>Informações sobre a fatura</h3>

<ul>
    <li><strong>Número de fatura único</strong> seguindo uma sequência cronológica</li>
    <li><strong>Data de emissão</strong> da fatura</li>
    <li><strong>Data de entrega</strong> ou de prestação (se diferente)</li>
</ul>

<h3>Detalhe das prestações</h3>

<ul>
    <li><strong>Descrição</strong> clara dos bens ou serviços</li>
    <li><strong>Quantidade</strong> e <strong>preço unitário sem IVA</strong></li>
    <li><strong>Taxa de IVA aplicável</strong> para cada linha</li>
    <li><strong>Montante de IVA</strong> por taxa</li>
    <li><strong>Total sem IVA, IVA e total com IVA</strong></li>
</ul>

<h2>A numeração das faturas</h2>

<p>A numeração das suas faturas deve respeitar regras estritas:</p>

<ul>
    <li><strong>Sequência única e cronológica</strong>: sem falhas na numeração</li>
    <li><strong>Formato livre</strong> mas coerente (ex: 2026-0001, FAC-2026-001)</li>
    <li><strong>Uma única série</strong> por exercício contabilístico (salvo casos particulares)</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Conselho</p>
    <p class="text-purple-700">Utilize um software de faturação como o faktur.lu para garantir automaticamente uma numeração conforme e evitar erros.</p>
</div>

<h2>As diferentes taxas de IVA no Luxemburgo</h2>

<p>O Luxemburgo aplica quatro taxas de IVA:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Taxa</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Aplicação</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Taxa normal (maioria dos bens e serviços)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Taxa intermédia (vinhos, certos combustíveis)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Taxa reduzida (gás, eletricidade, cabeleireiro)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Taxa super-reduzida (alimentação, livros, medicamentos)</td>
        </tr>
    </tbody>
</table>

<h2>Prazos de emissão e de conservação</h2>

<h3>Prazo de emissão</h3>

<p>Uma fatura deve ser emitida <strong>o mais tardar até ao dia 15 do mês seguinte</strong> à entrega do bem ou à conclusão da prestação.</p>

<h3>Prazo de conservação</h3>

<p>Deve conservar as suas faturas durante <strong>10 anos</strong> a partir do final do exercício contabilístico em causa. Esta obrigação aplica-se às faturas emitidas E recebidas.</p>

<h2>O ficheiro FAIA: uma obrigação luxemburguesa</h2>

<p>O <strong>FAIA (Ficheiro de Auditoria Informatizado)</strong> é um ficheiro XML normalizado que qualquer empresa que utilize um software de contabilidade ou de faturação deve poder produzir a pedido da administração fiscal.</p>

<p>Este ficheiro contém:</p>

<ul>
    <li>Todos os seus lançamentos contabilísticos</li>
    <li>As suas faturas emitidas e recebidas</li>
    <li>Os seus clientes e fornecedores</li>
    <li>Os seus pagamentos</li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ O faktur.lu gera automaticamente o seu ficheiro FAIA</p>
    <p class="text-green-700">O nosso software produz um ficheiro FAIA conforme com um clique, pronto a ser transmitido à AED em caso de controlo.</p>
</div>

<h2>Os erros a evitar</h2>

<ol>
    <li><strong>Esquecer o número de IVA</strong> nas faturas B2B intracomunitárias</li>
    <li><strong>Utilizar uma numeração não sequencial</strong> (falhas na série)</li>
    <li><strong>Não distinguir as taxas de IVA</strong> quando várias se aplicam</li>
    <li><strong>Emitir faturas em atraso</strong> (após o dia 15 do mês seguinte)</li>
    <li><strong>Não conservar as faturas durante 10 anos</strong></li>
</ol>

<h2>Conclusão</h2>

<p>A faturação no Luxemburgo exige rigor e conformidade. Ao utilizar um <strong>software de faturação adaptado</strong> como o faktur.lu, garante o cumprimento de todas as obrigações legais ganhando ao mesmo tempo um tempo precioso.</p>

<p>A nossa solução gera automaticamente faturas conformes com todas as menções obrigatórias, uma numeração correta e uma exportação FAIA integrada.</p><div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">exportação FAIA →</a></li><li><a href="/pt/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">taxas de IVA →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">menções obrigatórias →</a></li></ul></div>
HTML;
        return $this->base(
            'guide-complet-facturation-luxembourg-2026',
            1,
            '2026-02-13 14:03:49',
            'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article2(): array
    {
        $title = 'FAIA Luxemburgo: Tudo sobre o ficheiro de auditoria informatizado';
        $excerpt = 'O FAIA (Ficheiro de Auditoria Informatizado) é obrigatório no Luxemburgo. Descubra o que contém, quem o deve produzir e como gerar um ficheiro FAIA conforme.';
        $metaTitle = 'FAIA Luxemburgo: Guia Completo do Ficheiro de Auditoria Informatizado';
        $metaDescription = 'Tudo sobre o FAIA no Luxemburgo: definição, obrigações, conteúdo do ficheiro, como o gerar. Guia prático para estar em conformidade com a AED.';
        $content = <<<'HTML'
<p class="lead">O FAIA (Ficheiro de Auditoria Informatizado) é uma obrigação legal no Luxemburgo para todas as empresas que utilizam um software de contabilidade ou de faturação. Descubra o que precisa de saber para estar em conformidade.</p>

<h2>O que é o FAIA?</h2>

<p>O <strong>FAIA (Ficheiro de Auditoria Informatizado)</strong>, também chamado <strong>SAF-T Luxemburgo</strong>, é um ficheiro em formato XML normalizado que contém o conjunto dos dados contabilísticos e fiscais de uma empresa para um determinado período.</p>

<p>Este ficheiro foi introduzido pelo <strong>regulamento grão-ducal de 28 de janeiro de 2009</strong> e permite à Administração do Registo e dos Domínios (AED) realizar controlos fiscais de forma eficaz e automatizada.</p>

<h2>Quem deve produzir um ficheiro FAIA?</h2>

<p>A obrigação de produzir um ficheiro FAIA diz respeito a <strong>qualquer empresa ou pessoa</strong> que:</p>

<ul>
    <li>Esteja sujeita ao IVA no Luxemburgo</li>
    <li>Utilize um <strong>sistema informático</strong> para a sua contabilidade ou a sua faturação</li>
    <li>Seja objeto de um <strong>controlo fiscal</strong> da AED</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Importante</p>
    <p class="text-amber-700">O FAIA não deve ser transmitido de forma regular. Deve ser produzido <strong>a pedido</strong> da administração fiscal, geralmente no âmbito de um controlo.</p>
</div>

<h2>O que contém o ficheiro FAIA?</h2>

<p>O ficheiro FAIA está estruturado em várias secções contendo:</p>

<h3>1. Informações gerais (Header)</h3>

<ul>
    <li>Identificação da empresa (nome, endereço, número de IVA)</li>
    <li>Período coberto pelo ficheiro</li>
    <li>Informações sobre o software utilizado</li>
    <li>Data e hora da geração</li>
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
    <li>Todos os lançamentos do período</li>
    <li>Diários contabilísticos</li>
    <li>Documentos justificativos referenciados</li>
</ul>

<h3>5. Faturas (SourceDocuments)</h3>

<ul>
    <li>Faturas de venda emitidas</li>
    <li>Faturas de compra recebidas</li>
    <li>Notas de crédito</li>
    <li>Detalhe linha por linha com IVA</li>
</ul>

<h2>Formato técnico do FAIA</h2>

<p>O ficheiro FAIA deve respeitar especificações técnicas precisas:</p>

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
            <td class="border border-gray-300 px-4 py-2">FAIA_v2.01_2022.xsd (versão atual)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Período</td>
            <td class="border border-gray-300 px-4 py-2">Geralmente um exercício contabilístico completo</td>
        </tr>
    </tbody>
</table>

<h2>Como gerar um ficheiro FAIA conforme?</h2>

<p>Para produzir um ficheiro FAIA válido, tem várias opções:</p>

<h3>Opção 1: Software de faturação compatível</h3>

<p>É a solução mais simples. Um software como o <strong>faktur.lu</strong> gera automaticamente um ficheiro FAIA conforme a partir dos seus dados de faturação.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ Exportação FAIA com um clique no faktur.lu</p>
    <p class="text-green-700">O nosso software gera um ficheiro FAIA validado segundo o esquema XSD oficial, pronto a ser transmitido à AED.</p>
</div>

<h3>Opção 2: Software contabilístico</h3>

<p>Os softwares de contabilidade profissionais (Sage, BOB, etc.) propõem geralmente um módulo de exportação FAIA.</p>

<h3>Opção 3: Desenvolvimento à medida</h3>

<p>Para as grandes empresas com sistemas proprietários, um desenvolvimento específico pode ser necessário para extrair e formatar os dados segundo o esquema FAIA.</p>

<h2>Validação do ficheiro FAIA</h2>

<p>Antes de transmitir o seu ficheiro FAIA à administração, é recomendável validá-lo:</p>

<ol>
    <li><strong>Validação XSD</strong>: verificar que o ficheiro respeita o esquema XML oficial</li>
    <li><strong>Controlo dos totais</strong>: assegurar-se que as somas são coerentes</li>
    <li><strong>Verificação das referências</strong>: todos os identificadores (clientes, contas) devem estar presentes</li>
</ol>

<p>A AED disponibiliza uma <strong>ferramenta de validação online</strong> que permite verificar a conformidade técnica do seu ficheiro antes da submissão.</p>

<h2>Prazos e sanções</h2>

<h3>Prazo de produção</h3>

<p>Quando a AED solicita um ficheiro FAIA no âmbito de um controlo, a empresa dispõe geralmente de um <strong>prazo de 1 mês</strong> para o produzir.</p>

<h3>Sanções em caso de não conformidade</h3>

<p>O incumprimento da obrigação FAIA pode levar a:</p>

<ul>
    <li><strong>Coimas administrativas</strong></li>
    <li>Uma <strong>tributação oficiosa</strong> pela administração</li>
    <li>A <strong>rejeição da contabilidade</strong> como prova</li>
</ul>

<h2>Boas práticas</h2>

<ol>
    <li><strong>Teste regularmente</strong> a sua exportação FAIA, não apenas durante um controlo</li>
    <li><strong>Arquive</strong> os ficheiros FAIA gerados para cada exercício</li>
    <li><strong>Verifique a coerência</strong> entre as suas faturas e os seus lançamentos contabilísticos</li>
    <li><strong>Utilize um software certificado</strong> ou testado para a exportação FAIA</li>
</ol>

<h2>Conclusão</h2>

<p>O ficheiro FAIA é uma obrigação incontornável para as empresas luxemburguesas que utilizam ferramentas informáticas. Ao escolher um software de faturação compatível como o faktur.lu, garante poder produzir um ficheiro conforme em qualquer momento.</p>

<p>A nossa solução integra nativamente a exportação FAIA, validada segundo as últimas especificações da AED, para lhe permitir responder serenamente a qualquer pedido da administração fiscal.</p><div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">controlo fiscal →</a></li><li><a href="/pt/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">arquivo das faturas →</a></li></ul></div>
HTML;
        return $this->base(
            'faia-luxembourg-fichier-audit-informatise-guide',
            2,
            '2026-02-11 14:03:49',
            'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article3(): array
    {
        $title = 'IVA no Luxemburgo: taxas, cálculo e obrigações para as empresas';
        $excerpt = 'Domine o IVA luxemburguês: as diferentes taxas (17%, 14%, 8%, 3%), o cálculo, as declarações e os casos de isenção. Guia completo para as empresas.';
        $metaTitle = 'IVA Luxemburgo 2026: Taxas, Cálculo e Obrigações Fiscais';
        $metaDescription = 'Guia completo sobre o IVA no Luxemburgo: taxa normal 17%, taxas reduzidas, cálculo, declarações trimestrais. Tudo para gerir o IVA da sua empresa.';
        $content = <<<'HTML'
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
            <td class="border border-gray-300 px-4 py-2">Gás, eletricidade, cabeleireiro, obras de renovação</td>
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
    <li>Certas obras de renovação de habitações</li>
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

<h3>Declaração periódica de IVA</h3>

<p>Os sujeitos passivos devem entregar uma <strong>declaração de IVA</strong> de acordo com o seu volume de negócios:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Volume de negócios anual</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Periodicidade</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Superior a 620 000€</td>
            <td class="border border-gray-300 px-4 py-2"><strong>Mensal</strong></td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Entre 112 000€ e 620 000€</td>
            <td class="border border-gray-300 px-4 py-2"><strong>Trimestral</strong></td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Inferior a 112 000€</td>
            <td class="border border-gray-300 px-4 py-2"><strong>Anual</strong></td>
        </tr>
    </tbody>
</table>

<h3>Prazo de entrega</h3>

<p>A declaração deve ser entregue <strong>antes do dia 15 do mês seguinte</strong> ao período em causa (ou até 1 de março para as declarações anuais).</p>

<h3>Pagamento do IVA</h3>

<p>O pagamento do IVA devido deve acompanhar a declaração. Em caso de crédito de IVA, pode ser pedido um reembolso.</p>

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

<p>Para as vendas à distância a particulares, aplicam-se limiares. Acima de 10 000€ de vendas anuais para outros países da UE, deve registar-se no IVA nesses países ou utilizar o <strong>balcão único OSS</strong>.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ O faktur.lu gere automaticamente o IVA</p>
    <p class="text-green-700">O nosso software aplica a taxa de IVA correta consoante o tipo de cliente e gera as menções legais apropriadas nas suas faturas.</p>
</div>

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

<h2>Conclusão</h2>

<p>A gestão do IVA no Luxemburgo exige um bom conhecimento das taxas aplicáveis e das obrigações declarativas. Ao utilizar um software de faturação como o faktur.lu, beneficia de uma aplicação automática das taxas corretas e de faturas conformes às exigências legais.</p><div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/tva-intracommunautaire-guide-entreprises-luxembourgeoises" class="text-primary-500 hover:text-primary-600 text-sm">IVA intracomunitário →</a></li><li><a href="/pt/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">isenção de IVA →</a></li><li><a href="/pt/blog/facturer-etranger-depuis-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">faturar para o estrangeiro →</a></li></ul></div>
HTML;
        return $this->base(
            'tva-luxembourg-taux-calcul-obligations',
            2,
            '2026-02-08 14:03:49',
            'https://images.unsplash.com/photo-1554224154-26032ffc0d07?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article4(): array
    {
        $title = 'Freelancer no Luxemburgo: como faturar em total conformidade';
        $excerpt = 'É freelancer no Luxemburgo? Descubra como criar faturas conformes, gerir o IVA e respeitar todas as obrigações legais luxemburguesas.';
        $metaTitle = 'Freelancer Luxemburgo: Guia de Faturação Conforme 2026';
        $metaDescription = 'Guia completo para freelancers no Luxemburgo: criar faturas conformes, gerir o IVA, obrigações FAIA. Tudo para faturar em total legalidade.';
        $content = <<<'HTML'
<p class="lead">Está a lançar a sua atividade de freelancer no Luxemburgo? A faturação é um aspeto crucial da sua atividade. Este guia explica-lhe como criar faturas conformes e gerir as suas obrigações fiscais.</p>

<h2>O estatuto de freelancer no Luxemburgo</h2>

<p>No Luxemburgo, o freelancer (ou trabalhador independente) exerce geralmente sob um destes estatutos:</p>

<ul>
    <li><strong>Empresa individual</strong>: o estatuto mais comum para começar</li>
    <li><strong>Sociedade unipessoal (SARL-S)</strong>: uma sociedade por quotas simplificada</li>
    <li><strong>Profissão liberal</strong>: para certas atividades regulamentadas</li>
</ul>

<p>Qualquer que seja o seu estatuto, deve respeitar as mesmas regras de faturação.</p>

<h2>Registar-se no IVA</h2>

<p>Antes de começar a faturar, deve obter um <strong>número de IVA intracomunitário</strong> junto da Administração do Registo e dos Domínios (AED).</p>

<h3>Procedimento de registo</h3>

<ol>
    <li>Obter uma <strong>autorização de estabelecimento</strong> junto do Ministério da Economia</li>
    <li>Inscrever-se no <strong>Registo Comercial</strong> (RCS) se aplicável</li>
    <li>Solicitar o <strong>registo no IVA</strong> via MyGuichet.lu</li>
    <li>Receber o seu número no formato <strong>LU + 8 dígitos</strong></li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Atenção</p>
    <p class="text-amber-700">Nunca emita faturas sem número de IVA válido. As suas faturas seriam não conformes e exporia-se a sanções.</p>
</div>

<h2>As menções obrigatórias nas suas faturas</h2>

<p>Como freelancer, as suas faturas devem conter:</p>

<h3>As suas informações</h3>

<ul>
    <li><strong>Nome completo</strong> ou denominação social</li>
    <li><strong>Endereço profissional</strong> no Luxemburgo</li>
    <li><strong>Número de IVA</strong> (LU12345678)</li>
    <li><strong>Número de autorização de estabelecimento</strong></li>
    <li>Eventualmente o seu número RCS</li>
</ul>

<h3>Informações do cliente</h3>

<ul>
    <li>Nome ou denominação social</li>
    <li>Endereço completo</li>
    <li>Número de IVA (obrigatório para os clientes profissionais)</li>
</ul>

<h3>Detalhes da prestação</h3>

<ul>
    <li><strong>Número de fatura</strong> único e sequencial</li>
    <li><strong>Data de emissão</strong></li>
    <li><strong>Descrição detalhada</strong> dos serviços prestados</li>
    <li><strong>Número de horas ou dias</strong> (recomendado)</li>
    <li><strong>Tarifa unitária sem IVA</strong></li>
    <li><strong>Montante sem IVA, IVA e com IVA</strong></li>
    <li><strong>Taxa de IVA aplicável</strong></li>
</ul>

<h2>Que taxa de IVA aplicar?</h2>

<p>Como freelancer, aplica geralmente a <strong>taxa normal de 17%</strong> para a maioria das prestações de serviços.</p>

<h3>Casos particulares</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situação</th>
            <th class="border border-gray-300 px-4 py-2 text-left">IVA aplicável</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Cliente profissional no Luxemburgo</td>
            <td class="border border-gray-300 px-4 py-2">IVA 17%</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Cliente profissional na UE</td>
            <td class="border border-gray-300 px-4 py-2">Isento (autoliquidação)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Cliente fora da UE</td>
            <td class="border border-gray-300 px-4 py-2">Isento</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Cliente particular no Luxemburgo</td>
            <td class="border border-gray-300 px-4 py-2">IVA 17%</td>
        </tr>
    </tbody>
</table>

<h2>Faturar a um cliente no estrangeiro</h2>

<h3>Cliente profissional na UE</h3>

<p>Se o seu cliente é uma empresa noutro país da UE:</p>

<ol>
    <li><strong>Verifique o seu número de IVA</strong> no sistema VIES</li>
    <li><strong>Não aplique IVA</strong> na sua fatura</li>
    <li><strong>Adicione a menção</strong>: "Isenção de IVA - Artigo 44 da lei de 12 de fevereiro de 1979"</li>
    <li><strong>Indique o número de IVA</strong> do cliente na fatura</li>
</ol>

<h3>Cliente fora da UE</h3>

<p>Para os clientes situados fora da União Europeia, a prestação está isenta de IVA. Mencione "Isenção de IVA - Prestação de serviços fora da UE".</p>

<h2>A numeração das suas faturas</h2>

<p>As suas faturas devem seguir uma <strong>numeração cronológica e contínua</strong>:</p>

<ul>
    <li>Sem falhas na sequência</li>
    <li>Formato livre mas coerente (ex: 2026-001, 2026-002...)</li>
    <li>Uma única série por ano</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Conselho</p>
    <p class="text-purple-700">Utilize um software de faturação como o faktur.lu para gerar automaticamente números conformes e evitar erros.</p>
</div>

<h2>Gerir as suas declarações de IVA</h2>

<p>De acordo com o seu volume de negócios, deve apresentar declarações de IVA:</p>

<ul>
    <li><strong>Menos de 112 000€/ano</strong>: declaração anual</li>
    <li><strong>Entre 112 000€ e 620 000€/ano</strong>: declaração trimestral</li>
    <li><strong>Mais de 620 000€/ano</strong>: declaração mensal</li>
</ul>

<p>A declaração é feita online através do <strong>eCDF</strong> (eTVA).</p>

<h2>O ficheiro FAIA para os freelancers</h2>

<p>Se utiliza um software de faturação, deve ser capaz de produzir um <strong>ficheiro FAIA</strong> a pedido da administração fiscal.</p>

<p>Este ficheiro contém o conjunto das suas faturas e dados contabilísticos num formato XML normalizado.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ O faktur.lu gera o seu ficheiro FAIA</p>
    <p class="text-green-700">O nosso software produz automaticamente um ficheiro FAIA conforme, pronto para qualquer controlo fiscal.</p>
</div>

<h2>Conselhos para freelancers iniciantes</h2>

<ol>
    <li><strong>Utilize um software adaptado</strong> desde o início para evitar erros</li>
    <li><strong>Conserve todas as suas faturas</strong> (emitidas e recebidas) durante 10 anos</li>
    <li><strong>Separe as suas contas</strong> pessoais e profissionais</li>
    <li><strong>Fature rapidamente</strong> (nos 15 dias seguintes à prestação)</li>
    <li><strong>Verifique os números de IVA</strong> dos seus clientes da UE antes de faturar</li>
    <li><strong>Antecipe as suas declarações</strong> para evitar penalizações</li>
    <li><strong>Consulte um contabilista</strong> para as questões complexas</li>
</ol>

<h2>Erros frequentes a evitar</h2>

<ul>
    <li>❌ Faturar sem número de IVA</li>
    <li>❌ Esquecer menções obrigatórias</li>
    <li>❌ Aplicar uma taxa de IVA errada</li>
    <li>❌ Numeração não sequencial</li>
    <li>❌ Faturar em atraso (após o dia 15 do mês seguinte)</li>
    <li>❌ Não verificar os números de IVA da UE</li>
</ul>

<h2>Conclusão</h2>

<p>A faturação como freelancer no Luxemburgo não é complicada se seguir as regras. A utilização de um software de faturação adaptado como o faktur.lu permite-lhe criar faturas conformes em alguns cliques, com todas as menções obrigatórias e as taxas de IVA corretas aplicadas automaticamente.</p>

<p>Concentre-se no seu trabalho, nós tratamos da sua conformidade!</p><div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/5-erreurs-frequentes-facture-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">erros frequentes →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">menções obrigatórias →</a></li><li><a href="/pt/blog/automatiser-facturation-7-conseils-gagner-temps" class="text-primary-500 hover:text-primary-600 text-sm">automatizar a sua faturação →</a></li></ul></div>
HTML;
        return $this->base(
            'freelance-luxembourg-facturer-conformite',
            3,
            '2026-02-06 14:03:49',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article5(): array
    {
        $title = 'Menções obrigatórias numa fatura no Luxemburgo: checklist completa';
        $excerpt = 'Quais são as menções obrigatórias numa fatura luxemburguesa? Descubra a checklist completa para criar faturas conformes à legislação.';
        $metaTitle = 'Menções Obrigatórias Fatura Luxemburgo: Checklist 2026';
        $metaDescription = 'Lista completa das menções obrigatórias numa fatura no Luxemburgo. Checklist prática para criar faturas conformes e evitar sanções.';
        $content = <<<'HTML'
<p class="lead">Uma fatura não conforme pode ser rejeitada pelo seu cliente e expô-lo a sanções fiscais. Eis a checklist completa das menções obrigatórias para criar faturas luxemburguesas irrepreensíveis.</p>

<h2>Checklist das menções obrigatórias</h2>

<p>Segundo o artigo 63 da lei do IVA luxemburguesa, a sua fatura deve obrigatoriamente conter os seguintes elementos:</p>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações sobre o emissor</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nome completo ou denominação social</strong></li>
        <li>☐ <strong>Endereço completo</strong> da sede social</li>
        <li>☐ <strong>Número de IVA intracomunitário</strong> (formato LU + 8 dígitos)</li>
        <li>☐ <strong>Número RCS</strong> (se sociedade inscrita)</li>
        <li>☐ <strong>Forma jurídica</strong> (SARL, SA, etc.)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações sobre o cliente</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nome completo ou denominação social</strong></li>
        <li>☐ <strong>Endereço completo</strong></li>
        <li>☐ <strong>Número de IVA</strong> (obrigatório para B2B intracomunitário)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações sobre a fatura</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Número de fatura único</strong> (sequência cronológica)</li>
        <li>☐ <strong>Data de emissão</strong> da fatura</li>
        <li>☐ <strong>Data de entrega</strong> ou de prestação (se diferente)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Detalhe dos bens ou serviços</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Descrição clara</strong> e detalhada</li>
        <li>☐ <strong>Quantidade</strong> entregue ou prestada</li>
        <li>☐ <strong>Preço unitário sem IVA</strong></li>
        <li>☐ <strong>Reduções ou descontos</strong> eventuais</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informações IVA</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Taxa de IVA</strong> aplicável por linha</li>
        <li>☐ <strong>Montante de IVA</strong> por taxa</li>
        <li>☐ <strong>Base tributável</strong> por taxa de IVA</li>
        <li>☐ <strong>Menção de isenção</strong> se aplicável</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Totais</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Total sem IVA</strong></li>
        <li>☐ <strong>Total IVA</strong></li>
        <li>☐ <strong>Total com IVA</strong> (todos os impostos incluídos)</li>
    </ul>
</div>

<h2>Menções condicionais segundo os casos</h2>

<h3>Autoliquidação (cliente UE)</h3>

<p>Se faturar a um cliente profissional noutro país da UE sem IVA:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Isenção de IVA - Artigo 44 da lei de 12 de fevereiro de 1979 - Autoliquidação"</p>
</div>

<h3>Exportação fora da UE</h3>

<p>Para as vendas fora da União Europeia:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Isenção de IVA - Exportação fora da UE"</p>
</div>

<h3>Isenção de IVA</h3>

<p>Se beneficia da isenção de base (pequena empresa):</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"IVA não aplicável - Artigo 60bis da lei de 12 de fevereiro de 1979"</p>
</div>

<h3>Fatura de adiantamento</h3>

<p>Para uma fatura de adiantamento, acrescente:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Adiantamento sobre encomenda n.º [referência] de [data]"</p>
</div>

<h3>Nota de crédito</h3>

<p>Para uma nota de crédito, mencione obrigatoriamente:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Nota de crédito sobre fatura n.º [número] de [data]"</p>
</div>

<h2>A numeração das faturas</h2>

<p>A numeração deve respeitar estas regras:</p>

<ul>
    <li><strong>Única</strong>: cada fatura tem um número distinto</li>
    <li><strong>Cronológica</strong>: os números seguem-se pela ordem de emissão</li>
    <li><strong>Sem rutura</strong>: sem falhas na sequência</li>
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

<p>Embora não sejam estritamente obrigatórias, é recomendável indicar:</p>

<ul>
    <li><strong>Prazo de pagamento</strong> (ex: 30 dias)</li>
    <li><strong>Data de vencimento</strong></li>
    <li><strong>Dados bancários</strong> (IBAN, BIC)</li>
    <li><strong>Penalizações por atraso</strong> aplicáveis</li>
</ul>

<h2>Consequências de uma fatura não conforme</h2>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Riscos incorridos</p>
    <ul class="text-red-700 mt-2">
        <li>Rejeição da dedução do IVA pelo seu cliente</li>
        <li>Coimas administrativas da AED</li>
        <li>Liquidação adicional em caso de controlo</li>
        <li>Perda de credibilidade comercial</li>
    </ul>
</div>

<h2>Exemplo de fatura conforme</h2>

<p>Eis os elementos essenciais de uma fatura conforme:</p>

<div class="bg-gray-50 border border-gray-200 rounded-lg p-6 my-6 text-sm">
    <div class="flex justify-between mb-6">
        <div>
            <p class="font-bold">A Sua Empresa SARL</p>
            <p>123 rue du Commerce</p>
            <p>L-1234 Luxembourg</p>
            <p>IVA: LU12345678</p>
            <p>RCS: B123456</p>
        </div>
        <div class="text-right">
            <p class="font-bold text-xl">FATURA</p>
            <p>N.º 2026-0042</p>
            <p>Data: 15/02/2026</p>
        </div>
    </div>

    <div class="mb-6">
        <p class="font-semibold">Faturado a:</p>
        <p>Cliente Empresa SA</p>
        <p>456 avenue des Affaires</p>
        <p>L-5678 Luxembourg</p>
        <p>IVA: LU87654321</p>
    </div>

    <table class="w-full mb-6">
        <thead class="border-b-2 border-gray-300">
            <tr>
                <th class="text-left py-2">Descrição</th>
                <th class="text-right py-2">Qtd</th>
                <th class="text-right py-2">P.U. sem IVA</th>
                <th class="text-right py-2">IVA</th>
                <th class="text-right py-2">Total sem IVA</th>
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
        <p>Total sem IVA: <strong>750,00€</strong></p>
        <p>IVA 17%: <strong>127,50€</strong></p>
        <p class="text-lg">Total com IVA: <strong>877,50€</strong></p>
    </div>
</div>

<h2>Simplifique a sua vida com o faktur.lu</h2>

<p>Criar faturas conformes manualmente pode ser uma fonte de erros. O <strong>faktur.lu</strong> automatiza a conformidade:</p>

<ul>
    <li>✅ Todas as menções obrigatórias pré-preenchidas</li>
    <li>✅ Numeração automática e sequencial</li>
    <li>✅ Cálculo automático do IVA consoante o caso</li>
    <li>✅ Menções legais adaptadas (autoliquidação, exportação...)</li>
    <li>✅ Exportação FAIA integrada para os controlos fiscais</li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ Crie faturas conformes em 2 minutos</p>
    <p class="text-green-700">Experimente o faktur.lu gratuitamente durante 14 dias e descubra a simplicidade da faturação conforme no Luxemburgo.</p>
</div>

<h2>Conclusão</h2>

<p>Respeitar as menções obrigatórias nas suas faturas é essencial para estar em conformidade com a legislação luxemburguesa. Utilize esta checklist como referência e adote um software de faturação que automatize estas obrigações para se concentrar na sua atividade.</p><div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/note-de-credit-luxembourg-comment-etablir" class="text-primary-500 hover:text-primary-600 text-sm">nota de crédito →</a></li><li><a href="/pt/blog/freelance-luxembourg-facturer-conformite" class="text-primary-500 hover:text-primary-600 text-sm">freelancer →</a></li></ul></div>
HTML;
        return $this->base(
            'mentions-obligatoires-facture-luxembourg',
            1,
            '2026-02-03 14:03:49',
            'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article6(): array
    {
        $title = 'Criar uma empresa individual no Luxemburgo: Guia completo 2026';
        $excerpt = 'Descubra todas as etapas para criar a sua empresa individual no Luxemburgo: autorização de estabelecimento, registo no RCS, contribuições sociais e obrigações fiscais.';
        $metaTitle = 'Criar uma empresa individual no Luxemburgo | Guia 2026';
        $metaDescription = 'Guia completo para criar uma empresa individual no Luxemburgo: trâmites, custos (100-150€), prazos (1-3 meses), obrigações de IVA e contribuições sociais.';
        $content = <<<'HTML'
<p class="lead">O Luxemburgo oferece um ambiente favorável aos empreendedores com trâmites administrativos relativamente simples e custos de criação moderados. Este guia acompanha-o passo a passo na criação da sua empresa individual no Grão-Ducado.</p>

<h2>As formas jurídicas para empresa individual</h2>

<p>No Luxemburgo, o empreendedor independente exerce a sua profissão em nome próprio, na qualidade de:</p>

<ul>
    <li><strong>Comerciante</strong>: para as atividades comerciais</li>
    <li><strong>Artesão</strong>: para as atividades artesanais</li>
    <li><strong>Trabalhador intelectual independente</strong>: para as profissões liberais</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A reter</p>
    <p>Não existe equivalente exato ao estatuto de auto-empresário francês no Luxemburgo. A empresa individual é a forma mais próxima e mais simples.</p>
</div>

<h3>Características principais</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspeto</th>
            <th class="text-left p-2 bg-slate-100">Detalhe</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Personalidade jurídica</td><td class="p-2 border-b">Nenhuma - o empreendedor age em nome próprio</td></tr>
        <tr><td class="p-2 border-b">Capital mínimo</td><td class="p-2 border-b">Nenhum capital mínimo exigido</td></tr>
        <tr><td class="p-2 border-b">Responsabilidade</td><td class="p-2 border-b"><strong>Ilimitada</strong> - responsável pelo seu património pessoal</td></tr>
        <tr><td class="p-2 border-b">Formalismo</td><td class="p-2 border-b">Mínimo - sem estatutos a redigir</td></tr>
    </tbody>
</table>

<h2>Condições e pré-requisitos</h2>

<h3>Autorização de estabelecimento (obrigatória)</h3>

<p>Toda a atividade económica exercida de forma habitual exige uma <strong>autorização de estabelecimento prévia</strong>.</p>

<p><strong>Condições a cumprir:</strong></p>

<ul>
    <li><strong>Estabelecimento físico</strong>: instalação material apropriada no Luxemburgo</li>
    <li><strong>Gestão efetiva</strong>: presença física e gestão diária pelo titular</li>
    <li><strong>Honorabilidade profissional</strong>: registo criminal limpo, respeito das obrigações fiscais e sociais anteriores</li>
    <li><strong>Qualificação profissional</strong>: consoante a atividade visada</li>
</ul>

<h3>Qualificações profissionais requeridas</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tipo de atividade</th>
            <th class="text-left p-2 bg-slate-100">Qualificação requerida</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Atividades comerciais</td><td class="p-2 border-b">Geralmente sem diploma específico exigido</td></tr>
        <tr><td class="p-2 border-b">Atividades artesanais</td><td class="p-2 border-b">DAP, CATP ou Diploma de Mestre</td></tr>
        <tr><td class="p-2 border-b">Profissões liberais</td><td class="p-2 border-b">Diplomas específicos consoante a profissão</td></tr>
    </tbody>
</table>

<h2>Etapas de criação detalhadas</h2>

<h3>Etapa 1: Elaboração do projeto</h3>
<ul>
    <li>Redigir um plano de negócios</li>
    <li>Contactar os organismos de acompanhamento (House of Entrepreneurship, Câmara de Comércio, Câmara dos Ofícios)</li>
</ul>

<h3>Etapa 2: Verificação dos pré-requisitos</h3>
<ul>
    <li>Verificar a disponibilidade do nome comercial</li>
    <li>Assegurar-se que possui as qualificações requeridas</li>
    <li>Pedir o reconhecimento dos diplomas se necessário</li>
</ul>

<h3>Etapa 3: Pedido de autorização de estabelecimento</h3>
<p><strong>Onde:</strong> Online via MyGuichet.lu (com certificado LuxTrust) ou por correio postal</p>
<p><strong>Documentos requeridos:</strong></p>
<ul>
    <li>Formulário de pedido</li>
    <li>Comprovativos de qualificação profissional</li>
    <li>Certificado de registo criminal (boletim n.º 3)</li>
    <li>Cópia do bilhete de identidade</li>
    <li>Comprovativo de pagamento da taxa de chancelaria (50 EUR)</li>
</ul>

<h3>Etapa 4: Registo no RCS</h3>
<p><strong>Onde:</strong> Depósito eletrónico no site LBR (Luxembourg Business Registers)</p>
<p><strong>Documentos requeridos:</strong></p>
<ul>
    <li>Formulário de requisição</li>
    <li>Autorização de estabelecimento</li>
    <li>Documento de identidade</li>
    <li>Certidão de casamento / contrato de casamento (se aplicável)</li>
</ul>

<h3>Etapa 5: Inscrição na segurança social</h3>
<p>Inscrição junto do CCSS (Centre Commun de la Sécurité Sociale) como trabalhador independente.</p>

<h3>Etapa 6: Inscrição fiscal</h3>
<ul>
    <li>Inscrição junto da Administração das Contribuições Diretas</li>
    <li>Inscrição no IVA se o volume de negócios > 35 000 EUR</li>
</ul>

<h2>Custos de criação</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Rubrica</th>
            <th class="text-left p-2 bg-slate-100">Montante</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Autorização de estabelecimento</td><td class="p-2 border-b">50 EUR</td></tr>
        <tr><td class="p-2 border-b">Registo RCS</td><td class="p-2 border-b">~20-25 EUR</td></tr>
        <tr><td class="p-2 border-b">Reconhecimento de diploma</td><td class="p-2 border-b">75 EUR (se necessário)</td></tr>
        <tr><td class="p-2 border-b font-semibold">Total estimado</td><td class="p-2 border-b font-semibold">~100-150 EUR</td></tr>
    </tbody>
</table>

<h2>Prazos médios</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Trâmite</th>
            <th class="text-left p-2 bg-slate-100">Prazo</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Autorização de estabelecimento</td><td class="p-2 border-b">Até 3 meses</td></tr>
        <tr><td class="p-2 border-b">Reconhecimento de diploma</td><td class="p-2 border-b">2 a 6 semanas</td></tr>
        <tr><td class="p-2 border-b">Registo RCS</td><td class="p-2 border-b">Alguns dias</td></tr>
        <tr><td class="p-2 border-b font-semibold">Prazo total estimado</td><td class="p-2 border-b font-semibold">1 a 3 meses</td></tr>
    </tbody>
</table>

<h2>Obrigações após a criação</h2>

<h3>IVA (Imposto sobre o Valor Acrescentado)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situação</th>
            <th class="text-left p-2 bg-slate-100">Obrigação</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">VN anual ≤ 35 000 EUR</td><td class="p-2 border-b">Isenção de IVA (sem inscrição obrigatória)</td></tr>
        <tr><td class="p-2 border-b">VN anual > 35 000 EUR</td><td class="p-2 border-b">Inscrição obrigatória + declarações periódicas</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Menção obrigatória em isenção</p>
    <p>« IVA não aplicável, art. 57 do Código do IVA luxemburguês (Regime de isenção de imposto) »</p>
</div>

<h3>Contribuições sociais (CCSS)</h3>

<p>As contribuições representam cerca de <strong>25,3%</strong> do rendimento, repartidas da seguinte forma:</p>

<ul>
    <li>Seguro de doença (cuidados): 5,60%</li>
    <li>Seguro de doença (subsídios): 0,50%</li>
    <li>Seguro de dependência: 1,40%</li>
    <li>Seguro de pensão: 17,00%</li>
    <li>Seguro de acidentes: 0,65%</li>
    <li>Saúde no trabalho: 0,14%</li>
</ul>

<h3>Contabilidade</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Volume de negócios anual</th>
            <th class="text-left p-2 bg-slate-100">Obrigação</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">< 100 000 EUR sem IVA</td><td class="p-2 border-b">Contabilidade simplificada</td></tr>
        <tr><td class="p-2 border-b">≥ 100 000 EUR sem IVA</td><td class="p-2 border-b">Contabilidade normalizada obrigatória</td></tr>
    </tbody>
</table>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle_societe-personnes/entreprise-individuelle.html" target="_blank" rel="noopener">Guichet.lu - Empresa individual</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html" target="_blank" rel="noopener">Guichet.lu - Autorização de estabelecimento</a></li>
    <li><a href="https://lbr.lu/" target="_blank" rel="noopener">Luxembourg Business Registers (LBR)</a></li>
    <li><a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS - Trabalhadores independentes</a></li>
    <li><a href="https://www.houseofentrepreneurship.lu/" target="_blank" rel="noopener">House of Entrepreneurship</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Em resumo</p>
    <p>A criação de uma empresa individual no Luxemburgo é relativamente simples e pouco dispendiosa (cerca de 100-150 EUR). O processo demora geralmente 1 a 3 meses e inclui a obtenção da autorização de estabelecimento e o registo no RCS. As contribuições sociais representam cerca de 25% do rendimento.</p>
</div>
HTML;
        return $this->base(
            'creer-entreprise-individuelle-luxembourg-guide-2025',
            5,
            '2026-02-16 09:12:00',
            'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article7(): array
    {
        $title = 'Criar uma empresa individual em França: Guia completo 2026';
        $excerpt = 'Tudo o que precisa de saber para criar a sua empresa individual ou micro-empresa em França: trâmites via o balcão único INPI, regime fiscal, contribuições URSSAF e obrigações.';
        $metaTitle = 'Criar uma empresa individual em França | Guia 2026';
        $metaDescription = 'Guia completo para criar uma empresa individual em França: micro-empresa gratuita, balcão único INPI, SIRET em 1-2 semanas, contribuições 12-25%.';
        $content = <<<'HTML'
<p class="lead">A França oferece um quadro simplificado para criar a sua empresa individual, nomeadamente com o regime da micro-empresa. Desde 2023, todas as formalidades são feitas via o balcão único do INPI. Descubra as etapas, custos e obrigações para se lançar.</p>

<h2>As formas jurídicas para empresa individual</h2>

<h3>Empresa Individual (EI)</h3>

<p>A empresa individual permite exercer uma atividade em nome próprio, sem criação de pessoa coletiva.</p>

<ul>
    <li>Sem capital social exigido</li>
    <li>Sem estatutos a redigir</li>
    <li>Atividades possíveis: comerciais, artesanais, agrícolas ou liberais</li>
    <li><strong>Desde fevereiro de 2022</strong>: o património pessoal e profissional são automaticamente separados</li>
</ul>

<h3>Micro-empresa (Auto-empresário)</h3>

<p>A micro-empresa é um regime simplificado da empresa individual com limiares de volume de negócios:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tipo de atividade</th>
            <th class="text-left p-2 bg-slate-100">Limiar de VN (2026)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Venda de mercadorias, alojamento</td><td class="p-2 border-b">188 700 €</td></tr>
        <tr><td class="p-2 border-b">Prestações de serviços</td><td class="p-2 border-b">77 700 €</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Bom saber</p>
    <p>A EIRL deixou de existir desde 15 de maio de 2022. O novo estatuto EI integra agora a separação automática dos patrimónios.</p>
</div>

<h2>Condições e pré-requisitos</h2>

<h3>Condições pessoais</h3>

<ul>
    <li>Ser <strong>maior de idade</strong> (ou menor emancipado)</li>
    <li>Ter um <strong>endereço em França</strong></li>
    <li>Não estar sob tutela ou curatela</li>
    <li>Não estar abrangido por uma proibição de gerir</li>
    <li>Ser de nacionalidade francesa, europeia, ou ter um título de residência que autorize o exercício</li>
</ul>

<h3>Atividades regulamentadas</h3>

<p>Certas profissões exigem diplomas ou qualificações específicas: cabeleireiro, construção, profissões de saúde, etc.</p>

<h2>Etapas de criação via o Balcão Único INPI</h2>

<h3>Etapa 1: Preparação dos documentos</h3>
<ul>
    <li>Documento de identidade (bilhete de identidade ou passaporte) em formato PDF</li>
    <li>Comprovativo de morada (se a atividade for exercida em casa)</li>
    <li>Atestados de qualificação para as atividades regulamentadas</li>
</ul>

<h3>Etapa 2: Criação da conta</h3>
<p>Aceder a <a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">formalites.entreprises.gouv.fr</a> e criar uma conta via France Connect (recomendado) ou um identificador INPI.</p>

<h3>Etapa 3: Declaração de atividade</h3>
<ol>
    <li>Clicar em « Declarar »</li>
    <li>Selecionar « Empreendedor individual »</li>
    <li>Preencher: natureza da atividade, endereço, data de início, opções fiscais e sociais</li>
</ol>

<h3>Etapa 4: Validação e acompanhamento</h3>
<ul>
    <li>Anexar os documentos justificativos</li>
    <li>Proceder ao pagamento se necessário</li>
    <li>Acompanhar o progresso a partir do painel de controlo</li>
    <li>Inscrição automática no RNE (Registo Nacional das Empresas)</li>
</ul>

<h2>Custos de criação</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tipo de atividade</th>
            <th class="text-left p-2 bg-slate-100">Custo</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Atividade comercial</td><td class="p-2 border-b text-green-600 font-semibold">Gratuito</td></tr>
        <tr><td class="p-2 border-b">Atividade artesanal</td><td class="p-2 border-b text-green-600 font-semibold">Gratuito</td></tr>
        <tr><td class="p-2 border-b">Profissão liberal</td><td class="p-2 border-b text-green-600 font-semibold">Gratuito</td></tr>
        <tr><td class="p-2 border-b">Agente comercial</td><td class="p-2 border-b">23,86 €</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Atenção</p>
    <p>Desconfie dos sites privados que cobram taxas por um serviço normalmente gratuito.</p>
</div>

<h2>Prazos médios</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Etapa</th>
            <th class="text-left p-2 bg-slate-100">Prazo</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Declaração online</td><td class="p-2 border-b">Alguns minutos</td></tr>
        <tr><td class="p-2 border-b">Recibo de depósito</td><td class="p-2 border-b">24 horas</td></tr>
        <tr><td class="p-2 border-b">Obtenção do número SIRET</td><td class="p-2 border-b font-semibold">1 a 2 semanas</td></tr>
        <tr><td class="p-2 border-b">Notificação URSSAF</td><td class="p-2 border-b">4 a 10 semanas</td></tr>
    </tbody>
</table>

<h2>Obrigações após a criação</h2>

<h3>Contribuições URSSAF</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tipo de atividade</th>
            <th class="text-left p-2 bg-slate-100">Taxa 2024</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Compra-revenda</td><td class="p-2 border-b">12,3 %</td></tr>
        <tr><td class="p-2 border-b">Serviços comerciais/artesanais</td><td class="p-2 border-b">21,2 %</td></tr>
        <tr><td class="p-2 border-b">Outros serviços</td><td class="p-2 border-b">25,6 %</td></tr>
        <tr><td class="p-2 border-b">Profissões liberais (Cipav)</td><td class="p-2 border-b">23,2 %</td></tr>
    </tbody>
</table>

<p><strong>Frequência:</strong> Declaração mensal ou trimestral (à escolha). Obrigação de declarar mesmo que o VN seja nulo.</p>

<h3>IVA - Isenção de base</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tipo de atividade</th>
            <th class="text-left p-2 bg-slate-100">Limiar de base</th>
            <th class="text-left p-2 bg-slate-100">Limiar majorado</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Venda/Comércio/Alojamento</td><td class="p-2 border-b">85 000 €</td><td class="p-2 border-b">93 500 €</td></tr>
        <tr><td class="p-2 border-b">Prestações de serviços</td><td class="p-2 border-b">37 500 €</td><td class="p-2 border-b">41 250 €</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Menção obrigatória em isenção</p>
    <p>« IVA não aplicável, art. 293 B do CGI »</p>
</div>

<h3>CFE (Cotisation Foncière des Entreprises)</h3>

<ul>
    <li><strong>1.º ano:</strong> Isento de pagamento</li>
    <li><strong>Isenção total:</strong> Se VN anual < 5 000 €</li>
    <li><strong>Obrigação:</strong> Apresentar a declaração n.º 1447-C antes de 31 de dezembro do 1.º ano</li>
</ul>

<h3>Obrigações contabilísticas</h3>

<ol>
    <li>Estabelecer <strong>faturas conformes</strong> para cada venda/prestação</li>
    <li>Manter um <strong>livro de receitas</strong> cronológico</li>
    <li>Manter um <strong>registo de compras</strong> (se atividade de venda)</li>
    <li><strong>Conservar os documentos justificativos</strong> durante 10 anos</li>
</ol>

<h2>Apoios disponíveis</h2>

<h3>ACRE (Apoio aos Criadores e Sucessores de Empresa)</h3>

<ul>
    <li><strong>Isenção parcial</strong> das contribuições sociais no 1.º ano (até 50%)</li>
    <li>Condições: desempregados, beneficiários do RSA, jovens dos 18-25 anos, etc.</li>
    <li>Pedido a efetuar no momento da criação ou nos 45 dias seguintes</li>
</ul>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://entreprendre.service-public.gouv.fr/vosdroits/F37396" target="_blank" rel="noopener">Service Public - Empreendedor Individual</a></li>
    <li><a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">Balcão Único das Formalidades de Empresas</a></li>
    <li><a href="https://www.autoentrepreneur.urssaf.fr/" target="_blank" rel="noopener">URSSAF Auto-empresário</a></li>
    <li><a href="https://www.inpi.fr/realiser-demarches/formalites-dentreprises/creer-son-entreprise-individuelle-ei" target="_blank" rel="noopener">INPI - Criar a sua empresa individual</a></li>
    <li><a href="https://bpifrance-creation.fr/" target="_blank" rel="noopener">Bpifrance Création</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Em resumo</p>
    <p>Criar uma micro-empresa em França é gratuito e rápido (SIRET em 1-2 semanas). As contribuições sociais variam de 12 a 26% consoante a atividade. A isenção de IVA permite não faturar IVA abaixo de certos limiares.</p>
</div>
HTML;
        return $this->base(
            'creer-entreprise-individuelle-france-guide-2025',
            5,
            '2026-02-16 09:12:21',
            'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=1200',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article8(): array
    {
        $title = 'Criar uma empresa individual na Bélgica: Guia completo 2026';
        $excerpt = 'Como tornar-se trabalhador independente na Bélgica: inscrição na BCE através de um balcão de empresas, filiação a uma caixa social, obrigações de IVA e contribuições INASTI.';
        $metaTitle = 'Criar uma empresa individual na Bélgica | Guia 2026';
        $metaDescription = 'Guia completo para criar uma empresa em pessoa singular na Bélgica: custos (~200-500€), prazo (1-2 semanas), contribuições sociais 20,5%, isenção de IVA.';
        $content = <<<'HTML'
<p class="lead">A Bélgica oferece um quadro favorável aos trabalhadores independentes com trâmites simplificados desde a supressão dos conhecimentos de gestão de base. Este guia acompanha-o na criação da sua empresa em pessoa singular.</p>

<h2>Forma jurídica: empresa em pessoa singular</h2>

<p>A empresa em pessoa singular (trabalhador independente) é a forma mais simples para exercer sozinho uma atividade económica na Bélgica.</p>

<h3>Características principais</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspeto</th>
            <th class="text-left p-2 bg-slate-100">Detalhe</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Ato constitutivo</td><td class="p-2 border-b">Nenhum exigido</td></tr>
        <tr><td class="p-2 border-b">Capital mínimo</td><td class="p-2 border-b">Nenhum exigido</td></tr>
        <tr><td class="p-2 border-b">Responsabilidade</td><td class="p-2 border-b"><strong>Ilimitada</strong> - património pessoal e profissional confundidos</td></tr>
        <tr><td class="p-2 border-b">Estatísticas</td><td class="p-2 border-b">43% das PME belgas (510 346 empresas)</td></tr>
    </tbody>
</table>

<h2>Condições e pré-requisitos</h2>

<h3>Condições gerais</h3>

<ul>
    <li>Ter idade mínima de <strong>18 anos</strong></li>
    <li>Gozar dos seus direitos civis e políticos</li>
    <li>Ser legalmente capaz</li>
</ul>

<h3>Conhecimentos de gestão de base: SUPRIMIDOS</h3>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Boa notícia!</p>
    <p>Os conhecimentos de gestão de base foram suprimidos em todas as regiões:</p>
    <ul class="mt-2">
        <li><strong>Flandres:</strong> desde 2018</li>
        <li><strong>Bruxelas:</strong> desde 15 de janeiro de 2024</li>
        <li><strong>Valónia:</strong> desde 1 de outubro de 2025</li>
    </ul>
</div>

<h3>Acesso à profissão</h3>

<p>Certas profissões regulamentadas exigem ainda <strong>competências profissionais específicas</strong>: cabeleireiro, padeiro, pasteleiro, mecânico, telhador, técnico de aquecimento, restaurador, etc.</p>

<h2>Etapas de criação</h2>

<h3>Etapa 1: Abrir uma conta bancária profissional</h3>
<p>Obrigatória para separar as operações profissionais e privadas.</p>

<h3>Etapa 2: Inscrever-se no Banco-Encruzilhada das Empresas (BCE)</h3>
<ul>
    <li>Passar por um <strong>balcão de empresas autorizado</strong></li>
    <li>Obtenção do <strong>número de empresa</strong> (identificador único)</li>
    <li>Verificação das competências profissionais se necessário</li>
</ul>

<h3>Etapa 3: Ativar o número de IVA</h3>
<ul>
    <li>Junto da Administração geral da Fiscalidade</li>
    <li>Pode ser feito via o balcão de empresas</li>
    <li>Possibilidade de pedir o regime de isenção de IVA (se VN < 25 000 €)</li>
</ul>

<h3>Etapa 4: Filiar-se a uma caixa de seguros sociais</h3>
<p><strong>Obrigatório ANTES do início da atividade</strong>. Filiação possível até 6 meses antes.</p>

<h3>Etapa 5: Filiar-se a uma mutualidade</h3>
<p>Obrigatório para beneficiar do seguro de doença-invalidez.</p>

<h3>Etapa 6: Subscrever os seguros necessários</h3>
<p>Seguro de responsabilidade civil profissional e outros consoante a atividade.</p>

<h2>Os 8 balcões de empresas autorizados</h2>

<ol>
    <li>Liantis (o maior)</li>
    <li>Acerta</li>
    <li>Partena Professional</li>
    <li>UCM</li>
    <li>Xerius</li>
    <li>Securex</li>
    <li>Zenito</li>
    <li>Formalis</li>
</ol>

<h2>Custos de criação</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Rubrica</th>
            <th class="text-left p-2 bg-slate-100">Montante (2026)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Inscrição BCE via balcão</td><td class="p-2 border-b">109 - 111,50 € (isento de IVA)</td></tr>
        <tr><td class="p-2 border-b">Despesas diversas</td><td class="p-2 border-b">Variável</td></tr>
        <tr><td class="p-2 border-b font-semibold">Orçamento total estimado</td><td class="p-2 border-b font-semibold">200 - 500 €</td></tr>
    </tbody>
</table>

<h2>Prazos médios</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Trâmite</th>
            <th class="text-left p-2 bg-slate-100">Prazo</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Inscrição BCE via balcão</td><td class="p-2 border-b">Imediato a alguns dias</td></tr>
        <tr><td class="p-2 border-b">Ativação IVA</td><td class="p-2 border-b">Alguns dias</td></tr>
        <tr><td class="p-2 border-b">Filiação caixa social</td><td class="p-2 border-b">Imediata</td></tr>
        <tr><td class="p-2 border-b font-semibold">Processo completo</td><td class="p-2 border-b font-semibold">1 a 2 semanas</td></tr>
    </tbody>
</table>

<h2>Obrigações após a criação</h2>

<h3>IVA</h3>

<h4>Regime normal</h4>
<ul>
    <li>Declaração periódica de IVA (mensal ou trimestral)</li>
    <li>Faturação com IVA</li>
    <li>Listagem anual de clientes</li>
</ul>

<h4>Regime de isenção (se VN < 25 000 €)</h4>
<ul>
    <li>Sem declaração periódica</li>
    <li>Sem IVA a faturar nem a entregar</li>
    <li>Comunicação do VN anual antes de 31 de março</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Menção obrigatória em isenção</p>
    <p>« Pequena empresa sujeita ao regime de isenção de imposto - IVA não aplicável (Art. 56bis do Código do IVA) »</p>
</div>

<h3>Contribuições sociais (INASTI)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Escalão de rendimentos</th>
            <th class="text-left p-2 bg-slate-100">Taxa 2026</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">0 a 73 447,52 €</td><td class="p-2 border-b font-semibold">20,50%</td></tr>
        <tr><td class="p-2 border-b">73 447,52 a 108 238,40 €</td><td class="p-2 border-b">14,16%</td></tr>
        <tr><td class="p-2 border-b">Acima de 108 238,40 €</td><td class="p-2 border-b">Isento</td></tr>
    </tbody>
</table>

<p><strong>Contribuição mínima 2026:</strong> 450,15 €/trimestre (trabalhador independente a título principal)</p>

<p><strong>Funcionamento:</strong></p>
<ul>
    <li>Pagamento <strong>trimestral</strong></li>
    <li>Contribuições inicialmente <strong>provisórias</strong> (baseadas em rendimentos N-3)</li>
    <li>Regularização logo que os rendimentos definitivos sejam conhecidos</li>
</ul>

<h3>Obrigações contabilísticas</h3>

<h4>Contabilidade simplificada (VN < 500 000 €)</h4>
<p>3 diários obrigatórios:</p>
<ol>
    <li><strong>Diário de compras:</strong> lista das despesas</li>
    <li><strong>Diário de vendas:</strong> visão cronológica das faturas</li>
    <li><strong>Diário de tesouraria:</strong> livro de caixa + livro de banco</li>
</ol>

<p><strong>Conservação dos documentos:</strong> 7 anos</p>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://economie.fgov.be/fr/themes/entreprises/creer-une-entreprise/demarches-pour-un-travailleur" target="_blank" rel="noopener">SPF Economia - Trâmites para um trabalhador independente</a></li>
    <li><a href="https://1819.brussels/" target="_blank" rel="noopener">1819.brussels - Hub para empreendedores</a></li>
    <li><a href="https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer" target="_blank" rel="noopener">INASTI - Contribuições sociais</a></li>
    <li><a href="https://finances.belgium.be/fr/entreprises/tva/assujettissement-tva/regime-franchise-taxe" target="_blank" rel="noopener">SPF Finanças - Regime de isenção de IVA</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Em resumo</p>
    <p>Tornar-se trabalhador independente na Bélgica custa entre 200 e 500 € e demora 1 a 2 semanas. As contribuições sociais representam 20,5% do rendimento. A isenção de IVA é possível se o VN se mantiver abaixo dos 25 000 €/ano.</p>
</div>
HTML;
        return $this->base(
            'creer-entreprise-individuelle-belgique-guide-2025',
            5,
            '2026-02-16 09:12:21',
            'https://images.unsplash.com/photo-1559386484-97dfc0e15539?w=1200',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article9(): array
    {
        $title = 'Criar uma empresa individual na Alemanha: Guia completo 2026';
        $excerpt = 'Tudo o que precisa de saber para criar a sua Einzelunternehmen ou tornar-se Freiberufler na Alemanha: Gewerbeanmeldung, Finanzamt, Kleinunternehmerregelung e obrigações fiscais.';
        $metaTitle = 'Criar uma empresa individual na Alemanha | Guia 2026';
        $metaDescription = 'Guia completo para criar uma empresa individual na Alemanha: Gewerbeanmeldung (15-60€), prazo 1-3 dias, Kleinunternehmerregelung, obrigações IHK.';
        $content = <<<'HTML'
<p class="lead">A Alemanha oferece várias opções para criar uma empresa individual, com trâmites relativamente simples e rápidos. Este guia apresenta-lhe as diferentes formas jurídicas e as etapas para se lançar.</p>

<h2>As formas jurídicas para empresa individual</h2>

<h3>Einzelunternehmen (Empresa Individual)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Característica</th>
            <th class="text-left p-2 bg-slate-100">Detalhe</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Definição</td><td class="p-2 border-b">Empresa gerida por uma única pessoa</td></tr>
        <tr><td class="p-2 border-b">Capital mínimo</td><td class="p-2 border-b">Nenhum exigido</td></tr>
        <tr><td class="p-2 border-b">Responsabilidade</td><td class="p-2 border-b"><strong>Ilimitada</strong></td></tr>
        <tr><td class="p-2 border-b">Criação</td><td class="p-2 border-b">Gewerbeanmeldung + número fiscal</td></tr>
        <tr><td class="p-2 border-b">Tributação</td><td class="p-2 border-b">Imposto sobre o rendimento + Gewerbesteuer (se > 24 500 €/ano)</td></tr>
    </tbody>
</table>

<p><strong>Subcategorias:</strong></p>
<ul>
    <li><strong>Kleingewerbetreibender:</strong> Pequeno comerciante, sem inscrição no registo comercial</li>
    <li><strong>Eingetragener Kaufmann (e.K.):</strong> Inscrito no registo comercial</li>
</ul>

<h3>Freiberufler (Profissão Liberal)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Característica</th>
            <th class="text-left p-2 bg-slate-100">Detalhe</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Definição</td><td class="p-2 border-b">Atividade intelectual, criativa, científica ou educativa</td></tr>
        <tr><td class="p-2 border-b">Gewerbeanmeldung</td><td class="p-2 border-b text-green-600">NÃO exigida</td></tr>
        <tr><td class="p-2 border-b">Gewerbesteuer</td><td class="p-2 border-b text-green-600">NÃO aplicável</td></tr>
        <tr><td class="p-2 border-b">IHK/HWK</td><td class="p-2 border-b text-green-600">Sem contribuição obrigatória</td></tr>
        <tr><td class="p-2 border-b">Inscrição</td><td class="p-2 border-b">Diretamente no Finanzamt</td></tr>
    </tbody>
</table>

<p><strong>Profissões abrangidas (Katalogberufe):</strong> médicos, advogados, arquitetos, engenheiros, jornalistas, tradutores, artistas, professores...</p>

<h2>Condições e pré-requisitos</h2>

<h3>Para os Gewerbetreibende</h3>

<ul>
    <li><strong>Idade mínima:</strong> 18 anos (maioridade)</li>
    <li><strong>Residência:</strong> Endereço na Alemanha</li>
    <li><strong>Documentos:</strong> Passaporte ou bilhete de identidade</li>
    <li><strong>Atividade legal:</strong> Atividade autorizada pela lei</li>
</ul>

<h3>Documentos suplementares possíveis</h3>

<ul>
    <li><strong>Führungszeugnis</strong> (certificado de registo criminal): ~13 €</li>
    <li><strong>Gewerbezentralregisterauszug:</strong> ~13 €</li>
    <li><strong>Cartão de artesão:</strong> 80-250 €</li>
</ul>

<h2>Etapas de criação</h2>

<h3>Percurso A: Gewerbetreibender</h3>

<div class="bg-slate-50 p-4 rounded-lg my-4">
    <p class="font-mono text-sm">
        Etapa 1: Gewerbeanmeldung (Gewerbeamt)<br>
        ↓<br>
        Etapa 2: Notificações automáticas (Finanzamt, IHK/HWK, Berufsgenossenschaft)<br>
        ↓<br>
        Etapa 3: Fragebogen zur steuerlichen Erfassung (Finanzamt)<br>
        ↓<br>
        Etapa 4: Atribuição Steuernummer<br>
        ↓<br>
        Etapa 5: Inscrição Berufsgenossenschaft (7 dias)
    </p>
</div>

<h4>Gewerbeanmeldung</h4>
<ul>
    <li><strong>Onde:</strong> Gewerbeamt da comuna da sede</li>
    <li><strong>Formulário:</strong> GewA 1</li>
    <li><strong>Modo:</strong> Online (Gewerbe-Service-Portal) ou no local</li>
    <li><strong>Prazo:</strong> 1-3 dias</li>
</ul>

<h3>Percurso B: Freiberufler</h3>

<div class="bg-slate-50 p-4 rounded-lg my-4">
    <p class="font-mono text-sm">
        Etapa 1: Inscrição no Finanzamt (nas 4 semanas seguintes ao início)<br>
        ↓<br>
        Etapa 2: Fragebogen zur steuerlichen Erfassung<br>
        ↓<br>
        Etapa 3: Atribuição Steuernummer
    </p>
</div>

<h2>Custos de criação</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Rubrica</th>
            <th class="text-left p-2 bg-slate-100">Montante</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung (base)</td><td class="p-2 border-b">12,50 - 60 €</td></tr>
        <tr><td class="p-2 border-b">Grandes cidades (Munique, Estugarda)</td><td class="p-2 border-b">50 - 60 €</td></tr>
        <tr><td class="p-2 border-b">Pequenas comunas</td><td class="p-2 border-b">15 - 30 €</td></tr>
        <tr><td class="p-2 border-b">Freiberufler</td><td class="p-2 border-b text-green-600 font-semibold">0 € (gratuito)</td></tr>
    </tbody>
</table>

<h2>Prazos médios</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Etapa</th>
            <th class="text-left p-2 bg-slate-100">Prazo</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Tratamento Gewerbeanmeldung</td><td class="p-2 border-b font-semibold">1-3 dias</td></tr>
        <tr><td class="p-2 border-b">Confirmação escrita Gewerbeamt</td><td class="p-2 border-b">3 dias máximo</td></tr>
        <tr><td class="p-2 border-b">Receção Fragebogen Finanzamt</td><td class="p-2 border-b">4-6 semanas</td></tr>
        <tr><td class="p-2 border-b">Atribuição Steuernummer</td><td class="p-2 border-b">2-4 semanas</td></tr>
        <tr><td class="p-2 border-b font-semibold">Prazo total</td><td class="p-2 border-b font-semibold">6-10 semanas</td></tr>
    </tbody>
</table>

<h2>Obrigações após a criação</h2>

<h3>IVA / Umsatzsteuer</h3>

<h4>Regime normal</h4>
<ul>
    <li><strong>Taxa padrão:</strong> 19%</li>
    <li><strong>Taxa reduzida:</strong> 7%</li>
    <li>Declaração mensal ou trimestral (Umsatzsteuer-Voranmeldung)</li>
</ul>

<h4>Kleinunternehmerregelung (§ 19 UStG)</h4>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Critério</th>
            <th class="text-left p-2 bg-slate-100">Limiar 2026</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">VN ano anterior</td><td class="p-2 border-b">≤ 25 000 €</td></tr>
        <tr><td class="p-2 border-b">VN ano em curso</td><td class="p-2 border-b">≤ 100 000 €</td></tr>
    </tbody>
</table>

<p><strong>Vantagens:</strong></p>
<ul>
    <li>Sem faturação de IVA</li>
    <li>Sem declarações de IVA</li>
    <li>Contabilidade simplificada</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Menção obrigatória nas faturas</p>
    <p>« Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG »<br>
    <em>(Sem indicação de IVA, regime dos pequenos empresários nos termos do § 19 UStG)</em></p>
</div>

<h3>Gewerbesteuer (Imposto Profissional)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situação</th>
            <th class="text-left p-2 bg-slate-100">Obrigação</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Freiberufler</td><td class="p-2 border-b text-green-600">Isento</td></tr>
        <tr><td class="p-2 border-b">Gewerbetreibender < 24 500 €/ano</td><td class="p-2 border-b text-green-600">Isento (Freibetrag)</td></tr>
        <tr><td class="p-2 border-b">Gewerbetreibender ≥ 24 500 €/ano</td><td class="p-2 border-b">Sujeito</td></tr>
    </tbody>
</table>

<h3>Contribuições sociais</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tipo</th>
            <th class="text-left p-2 bg-slate-100">Obrigação</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Krankenversicherung (doença)</td><td class="p-2 border-b text-red-600 font-semibold">OBRIGATÓRIO</td></tr>
        <tr><td class="p-2 border-b">Pflegeversicherung (dependência)</td><td class="p-2 border-b text-red-600 font-semibold">OBRIGATÓRIO</td></tr>
        <tr><td class="p-2 border-b">Rentenversicherung (reforma)</td><td class="p-2 border-b">Facultativa*</td></tr>
        <tr><td class="p-2 border-b">Arbeitslosenversicherung (desemprego)</td><td class="p-2 border-b">Facultativa</td></tr>
    </tbody>
</table>

<p><small>*Obrigatória para certas profissões (artesãos, professores, prestadores de cuidados)</small></p>

<h3>Contribuição IHK/HWK</h3>

<ul>
    <li>Adesão automática e obrigatória para Gewerbetreibende</li>
    <li>Isenção se Gewerbeertrag < 5 200 €/ano</li>
    <li>Contribuição progressiva acima desse valor</li>
</ul>

<h2>Tabela comparativa</h2>

<table class="w-full my-4 text-sm">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Critério</th>
            <th class="text-left p-2 bg-slate-100">Einzelunternehmen</th>
            <th class="text-left p-2 bg-slate-100">Freiberufler</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung</td><td class="p-2 border-b">Sim</td><td class="p-2 border-b text-green-600">Não</td></tr>
        <tr><td class="p-2 border-b">Gewerbesteuer</td><td class="p-2 border-b">Sim (> 24 500 €)</td><td class="p-2 border-b text-green-600">Não</td></tr>
        <tr><td class="p-2 border-b">IHK-Mitgliedschaft</td><td class="p-2 border-b">Obrigatória</td><td class="p-2 border-b text-green-600">Não</td></tr>
        <tr><td class="p-2 border-b">Custo criação</td><td class="p-2 border-b">12,50-60 €</td><td class="p-2 border-b text-green-600">0 €</td></tr>
        <tr><td class="p-2 border-b">Prazo criação</td><td class="p-2 border-b">1-3 dias</td><td class="p-2 border-b text-green-600">Imediato</td></tr>
    </tbody>
</table>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://www.existenzgruendungsportal.de/" target="_blank" rel="noopener">Existenzgründungsportal (BMWK)</a></li>
    <li><a href="https://www.bmwk.de/" target="_blank" rel="noopener">Bundesministerium für Wirtschaft (BMWK)</a></li>
    <li><a href="https://www.ihk.de/" target="_blank" rel="noopener">IHK - Industrie- und Handelskammer</a></li>
    <li><a href="https://www.deutsche-rentenversicherung.de/" target="_blank" rel="noopener">Deutsche Rentenversicherung</a></li>
    <li><a href="https://gruenderplattform.de/" target="_blank" rel="noopener">Gründerplattform</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Em resumo</p>
    <p>Criar uma empresa individual na Alemanha custa entre 0 e 60 € consoante o estatuto. A Gewerbeanmeldung é tratada em 1-3 dias. O regime Kleinunternehmerregelung permite estar isento de IVA abaixo de certos limiares. Os Freiberufler beneficiam de um regime simplificado sem Gewerbesteuer nem contribuição IHK.</p>
</div>
HTML;
        return $this->base(
            'creer-entreprise-individuelle-allemagne-guide-2025',
            5,
            '2026-02-16 09:12:21',
            'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?w=1200',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article37(): array
    {
        $title = 'Peppol B2G no Luxemburgo: guia completo 2026';
        $excerpt = 'Peppol é o standard europeu de faturação eletrónica para o setor público. Descubra como enviar as suas faturas B2G no Luxemburgo através da rede Peppol.';
        $metaTitle = 'Peppol B2G Luxemburgo: Guia Completo 2026 | Faturação Eletrónica';
        $metaDescription = 'Tudo sobre Peppol B2G no Luxemburgo: obrigações, funcionamento, como enviar as suas faturas eletrónicas ao setor público. Guia prático 2026.';
        $content = <<<'HTML'
<p class="lead">Desde 2022, a faturação eletrónica via <strong>Peppol</strong> tornou-se uma obrigação para os fornecedores do setor público no Luxemburgo. Este guia explica-lhe tudo o que precisa de saber para se colocar em conformidade.</p>

<h2>O que é o Peppol?</h2>

<p><strong>Peppol (Pan-European Public Procurement OnLine)</strong> é uma rede internacional de faturação eletrónica que permite enviar documentos comerciais (faturas, notas de encomenda) de forma normalizada entre empresas e administrações públicas.</p>

<p>No Luxemburgo, Peppol é o canal oficial para a faturação eletrónica B2G (Business-to-Government). Isto significa que qualquer empresa que fature ao Estado, às comunas ou aos estabelecimentos públicos deve utilizar este formato.</p>

<h2>Quem é abrangido?</h2>

<p>Se for fornecedor de uma das seguintes entidades, deve faturar via Peppol:</p>

<ul>
    <li><strong>O Estado luxemburguês</strong> e os seus ministérios</li>
    <li><strong>As comunas</strong> luxemburguesas</li>
    <li><strong>Os estabelecimentos públicos</strong> (hospitais, escolas, etc.)</li>
    <li><strong>Os contratos públicos</strong> de mais de 30 000 EUR</li>
</ul>

<p>As empresas que apenas faturam a clientes privados (B2B ou B2C) ainda não são abrangidas por esta obrigação, mas a União Europeia caminha para uma generalização a partir de 2028.</p>

<h2>Como funciona o Peppol?</h2>

<p>A rede Peppol funciona segundo um modelo de "quatro cantos":</p>

<ol>
    <li><strong>O remetente</strong> (a sua empresa) cria a fatura</li>
    <li><strong>O ponto de acesso remetente</strong> (o seu software de faturação) envia a fatura para a rede Peppol</li>
    <li><strong>O ponto de acesso destinatário</strong> (lado da administração) recebe a fatura</li>
    <li><strong>O destinatário</strong> (a administração pública) processa a fatura</li>
</ol>

<p>Cada participante na rede é identificado por um <strong>Peppol ID</strong> único. No Luxemburgo, este número é geralmente baseado no número de IVA (esquema 0184).</p>

<h2>O formato UBL</h2>

<p>As faturas Peppol utilizam o formato <strong>UBL (Universal Business Language)</strong>, um standard XML internacional. A sua fatura deve conter:</p>

<ul>
    <li>As informações do remetente (nome, endereço, IVA)</li>
    <li>As informações do destinatário (nome, Peppol ID)</li>
    <li>As linhas de faturação (descrição, quantidade, preço unitário)</li>
    <li>Os montantes de IVA discriminados por taxa</li>
    <li>Os totais (sem IVA, IVA, com IVA)</li>
    <li>As referências de encomenda ou de contrato</li>
</ul>

<h2>Como enviar uma fatura Peppol a partir do faktur.lu?</h2>

<p>Com o <strong>faktur.lu</strong>, o envio de faturas Peppol está integrado diretamente no software:</p>

<ol>
    <li>Crie a sua fatura normalmente</li>
    <li>Assegure-se de que o Peppol ID do cliente está preenchido</li>
    <li>Clique em <strong>"Enviar via Peppol"</strong></li>
    <li>A fatura é convertida em UBL e transmitida via a rede Peppol</li>
    <li>Recebe uma confirmação de receção</li>
</ol>

<p>O plano Essencial inclui 10 envios Peppol por mês, e o plano Pro oferece envios ilimitados.</p>

<h2>Vantagens da faturação Peppol</h2>

<ul>
    <li><strong>Tratamento acelerado</strong>: as faturas Peppol são processadas automaticamente, reduzindo os prazos de pagamento</li>
    <li><strong>Redução dos erros</strong>: o formato estruturado elimina os erros de introdução manual</li>
    <li><strong>Rastreabilidade</strong>: cada fatura é rastreada de ponta a ponta na rede</li>
    <li><strong>Conformidade</strong>: respeita as obrigações legais luxemburguesas</li>
    <li><strong>Interoperabilidade</strong>: Peppol é reconhecido em mais de 35 países</li>
</ul>

<h2>Perguntas frequentes</h2>

<h3>A faturação Peppol é obrigatória para o B2B?</h3>

<p>Ainda não no Luxemburgo. No entanto, a diretiva europeia ViDA prevê uma generalização da faturação eletrónica para as transações intracomunitárias a partir de 2028-2030.</p>

<h3>Quanto custa o envio via Peppol?</h3>

<p>Com o faktur.lu, o envio Peppol está incluído na sua subscrição. O plano Essencial inclui 10 envios/mês e o plano Pro oferece envios ilimitados.</p>

<h3>Como encontrar o Peppol ID de uma administração?</h3>

<p>Os Peppol ID das administrações luxemburguesas estão disponíveis no <a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a>. Pode também procurá-los diretamente no faktur.lu durante a criação do cliente.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/factur-x-zugferd-facturation-electronique-europeenne" class="text-primary-500 hover:text-primary-600 text-sm">Factur-X / ZUGFeRD →</a></li><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">software de faturação →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Pronto a faturar via Peppol?</h3>
    <p class="text-primary-800 mb-4">O faktur.lu integra nativamente a transmissão Peppol. Crie a sua conta gratuita e envie a sua primeira fatura eletrónica em poucos minutos.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'peppol-b2g-luxembourg-guide-complet-2026',
            2,
            '2026-04-12 08:31:02',
            'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article38(): array
    {
        $title = 'Livro de receitas no Luxemburgo: obrigações e modelo';
        $excerpt = 'O livro de receitas é uma obrigação contabilística no Luxemburgo. Aprenda o que deve conter e como o manter atualizado facilmente.';
        $metaTitle = 'Livro de Receitas Luxemburgo: Obrigações e Modelo | Guia 2026';
        $metaDescription = 'O livro de receitas é obrigatório no Luxemburgo. Descubra as menções exigidas, o formato legal e como gerá-lo automaticamente com o faktur.lu.';
        $content = <<<'HTML'
<p class="lead">O <strong>livro de receitas</strong> é um documento contabilístico obrigatório no Luxemburgo para todos os contribuintes sujeitos ao IVA. Recenseia o conjunto das receitas cobradas durante um exercício fiscal. Eis tudo o que precisa de saber.</p>

<h2>O que é o livro de receitas?</h2>

<p>O livro de receitas é um registo cronológico que enumera todas as faturas emitidas e os pagamentos recebidos pela sua empresa. Faz parte das <strong>obrigações contabilísticas</strong> definidas pela lei fiscal luxemburguesa.</p>

<p>Ao contrário do livro razão contabilístico, o livro de receitas é um documento simplificado, principalmente destinado aos <strong>trabalhadores independentes, freelancers e pequenas empresas</strong> que não mantêm uma contabilidade em partidas dobradas.</p>

<h2>Quem deve manter um livro de receitas?</h2>

<ul>
    <li><strong>Todos os sujeitos passivos do IVA</strong> no Luxemburgo</li>
    <li><strong>Os trabalhadores independentes e profissões liberais</strong></li>
    <li><strong>Os auto-empresários</strong> mesmo em isenção de IVA (abaixo do limiar de 35 000 EUR)</li>
    <li><strong>As sociedades</strong> (em complemento da sua contabilidade)</li>
</ul>

<h2>O que deve conter o livro de receitas?</h2>

<p>Cada entrada do livro de receitas deve mencionar:</p>

<ul>
    <li><strong>A data</strong> da fatura ou do pagamento</li>
    <li><strong>O número da fatura</strong> (numeração sequencial)</li>
    <li><strong>O nome do cliente</strong></li>
    <li><strong>A descrição</strong> da prestação ou do bem vendido</li>
    <li><strong>O montante sem IVA</strong></li>
    <li><strong>A taxa de IVA aplicada</strong> (17%, 14%, 8% ou 3%)</li>
    <li><strong>O montante do IVA</strong></li>
    <li><strong>O montante com IVA</strong></li>
</ul>

<h2>Formato e conservação</h2>

<p>O livro de receitas pode ser mantido:</p>

<ul>
    <li><strong>Em formato papel</strong>: num caderno dedicado, sem rasuras nem espaços em branco</li>
    <li><strong>Em formato digital</strong>: através de um software de faturação, uma folha de cálculo ou um ficheiro PDF</li>
</ul>

<p>Importante: o livro de receitas deve ser conservado durante <strong>10 anos</strong> a contar do final do exercício fiscal, em conformidade com o artigo 60 da lei geral dos impostos (Abgabenordnung).</p>

<h2>Gerar o seu livro de receitas com o faktur.lu</h2>

<p>Com o <strong>faktur.lu</strong>, o seu livro de receitas é gerado automaticamente a partir das suas faturas:</p>

<ol>
    <li>Vá a <strong>Contabilidade > Livro de receitas</strong></li>
    <li>Selecione o período pretendido (mês, trimestre, ano)</li>
    <li>Consulte o detalhe de cada fatura com discriminação do IVA</li>
    <li>Exporte em <strong>PDF</strong> ou <strong>CSV</strong> para o seu contabilista</li>
</ol>

<p>Todas as menções obrigatórias são incluídas automaticamente, e o livro está conforme às exigências da <strong>Administração das contribuições diretas (ACD)</strong>.</p>

<h2>Livro de receitas vs exportação FAIA</h2>

<p>Não confunda o livro de receitas com a exportação FAIA:</p>

<ul>
    <li>O <strong>livro de receitas</strong> é um documento de acompanhamento corrente, utilizado no quotidiano e transmitido ao seu contabilista</li>
    <li>O <strong>FAIA</strong> é um ficheiro XML exigido apenas em caso de controlo fiscal pela AED (Administração do registo, dos domínios e do IVA)</li>
</ul>

<p>Ambos são gerados automaticamente pelo faktur.lu.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">exportação FAIA →</a></li><li><a href="/pt/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">arquivo →</a></li><li><a href="/pt/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">controlo fiscal →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Gere o seu livro de receitas com 1 clique</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera automaticamente o seu livro de receitas conforme à legislação luxemburguesa, com exportação PDF e CSV.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'livre-des-recettes-luxembourg-obligations-modele',
            2,
            '2026-04-12 08:31:02',
            'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article39(): array
    {
        $title = 'Arquivo das faturas no Luxemburgo: prazo legal e formato';
        $excerpt = 'No Luxemburgo, as faturas devem ser conservadas durante 10 anos. Descubra as regras de arquivo, os formatos aceites e as boas práticas.';
        $metaTitle = 'Arquivo Faturas Luxemburgo: Prazo Legal e Formato | Guia';
        $metaDescription = 'Quanto tempo conservar as suas faturas no Luxemburgo? Descubra o prazo legal (10 anos), os formatos aceites e como arquivar em PDF/A.';
        $content = <<<'HTML'
<p class="lead">O arquivo das faturas é uma obrigação legal no Luxemburgo. Toda a empresa deve conservar os seus documentos contabilísticos durante um período mínimo de <strong>10 anos</strong>. Eis as regras a conhecer para estar em conformidade.</p>

<h2>Prazo legal de conservação</h2>

<p>Segundo o artigo 60 do Abgabenordnung (AO) e o Código do comércio luxemburguês, os documentos contabilísticos devem ser conservados durante:</p>

<ul>
    <li><strong>10 anos</strong> para as faturas (emitidas e recebidas)</li>
    <li><strong>10 anos</strong> para os livros de contabilidade</li>
    <li><strong>10 anos</strong> para os documentos justificativos</li>
    <li><strong>10 anos</strong> para a correspondência comercial</li>
</ul>

<p>O prazo conta-se a partir do <strong>final do exercício fiscal</strong> a que o documento se refere. Uma fatura emitida em 15 de março de 2026 deverá portanto ser conservada até 31 de dezembro de 2036.</p>

<h2>Formatos de arquivo aceites</h2>

<p>A administração fiscal luxemburguesa aceita dois tipos de arquivo:</p>

<h3>Arquivo em papel</h3>
<p>Os originais em papel devem ser conservados num local seco e acessível. As faturas não devem ser alteradas.</p>

<h3>Arquivo digital</h3>
<p>O arquivo eletrónico é autorizado sob certas condições:</p>

<ul>
    <li>O formato deve garantir a <strong>integridade</strong> do documento (sem possibilidade de modificação)</li>
    <li>O documento deve estar <strong>legível</strong> durante todo o período de conservação</li>
    <li>O formato <strong>PDF/A</strong> (ISO 19005) é recomendado por garantir a perenidade</li>
    <li>Uma <strong>impressão digital</strong> (hash) pode provar que o documento não foi modificado</li>
</ul>

<h2>Porquê o formato PDF/A?</h2>

<p>O <strong>PDF/A</strong> é uma norma ISO especificamente concebida para o arquivo a longo prazo. Ao contrário de um PDF padrão:</p>

<ul>
    <li><strong>Incorpora todas as fontes</strong> utilizadas (sem dependência externa)</li>
    <li><strong>Proíbe o JavaScript</strong> e os elementos multimédia</li>
    <li>Garante que o documento estará <strong>legível em 10, 20 ou 50 anos</strong></li>
    <li>É <strong>reconhecido pelas administrações fiscais</strong> europeias</li>
</ul>

<h2>Arquivar as suas faturas com o faktur.lu</h2>

<p>O plano Pro do faktur.lu inclui o <strong>arquivo automático em PDF/A</strong>:</p>

<ol>
    <li>Cada fatura finalizada é automaticamente arquivada em PDF/A</li>
    <li>Uma impressão digital (checksum SHA-256) é calculada</li>
    <li>Os arquivos são conservados de forma segura durante 10 anos</li>
    <li>Pode transferir os seus arquivos a qualquer momento</li>
</ol>

<h2>Riscos em caso de não conformidade</h2>

<p>Em caso de controlo fiscal, a ausência de faturas ou um arquivo não conforme pode implicar:</p>

<ul>
    <li>A <strong>rejeição das deduções de IVA</strong> nas faturas em falta</li>
    <li><strong>Penalizações financeiras</strong></li>
    <li>Uma <strong>estimativa oficiosa</strong> do volume de negócios pela administração</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">ficheiro FAIA →</a></li><li><a href="/pt/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">controlo fiscal →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Arquivo automático com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">Não se preocupe mais com o arquivo. O faktur.lu arquiva automaticamente as suas faturas em PDF/A com impressão digital, conforme durante 10 anos.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'archivage-factures-luxembourg-duree-legale-format',
            2,
            '2026-04-12 08:31:02',
            'https://images.unsplash.com/photo-1568667256549-094345857637?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article40(): array
    {
        $title = 'Como cobrar a um cliente que não paga no Luxemburgo';
        $excerpt = 'Um cliente não paga? Descubra como gerir as faturas em atraso no Luxemburgo: avisos amigáveis, interpelações e recursos legais.';
        $metaTitle = 'Cobrar a um Cliente Devedor no Luxemburgo: Guia Prático';
        $metaDescription = 'Como cobrar a um cliente que não paga? Descubra as etapas, modelos de email e quadro legal das cobranças de pagamento no Luxemburgo.';
        $content = <<<'HTML'
<p class="lead">As faturas por pagar são o pesadelo de qualquer empreendedor. No Luxemburgo, <strong>30% das faturas são pagas com atraso</strong>. Eis como gerir as cobranças eficazmente, do lembrete amigável à interpelação formal.</p>

<h2>Etapa 1: O lembrete amigável (D+7 após o vencimento)</h2>

<p>Um simples esquecimento? É frequentemente o caso. O primeiro lembrete deve ser <strong>cortês e profissional</strong>:</p>

<ul>
    <li>Envie um email recordando o número e o montante da fatura</li>
    <li>Anexe uma cópia da fatura original</li>
    <li>Proponha um novo prazo de pagamento se necessário</li>
    <li>Mantenha-se factual e cordial</li>
</ul>

<p><strong>Conselho:</strong> com o faktur.lu, pode automatizar este primeiro lembrete. O sistema deteta as faturas em atraso e envia um email de cobrança automático.</p>

<h2>Etapa 2: A cobrança formal (D+15)</h2>

<p>Se o primeiro lembrete ficar sem resposta, envie uma <strong>cobrança mais formal</strong>:</p>

<ul>
    <li>Mencione claramente o atraso de pagamento</li>
    <li>Recorde as condições de pagamento acordadas</li>
    <li>Indique que poderão ser aplicados juros de mora</li>
    <li>Defina um prazo preciso (ex: 8 dias)</li>
</ul>

<h2>Etapa 3: A interpelação formal (D+30)</h2>

<p>A interpelação formal é um documento formal que constitui uma <strong>prova jurídica</strong>. Deve ser enviada por <strong>carta registada com aviso de receção</strong> e conter:</p>

<ul>
    <li>A menção <strong>"Interpelação formal"</strong> no assunto</li>
    <li>O detalhe das faturas por pagar (números, montantes, datas)</li>
    <li>O montante total devido (capital + juros de mora eventuais)</li>
    <li>Um <strong>prazo de 8 dias</strong> para regularizar</li>
    <li>A menção de que se reserva o direito de iniciar processos judiciais</li>
</ul>

<h2>Os juros de mora no Luxemburgo</h2>

<p>No Luxemburgo, os juros de mora são regidos pela <strong>lei de 18 de abril de 2004</strong> sobre os prazos de pagamento:</p>

<ul>
    <li><strong>Transações B2B</strong>: a taxa de juro de mora é a taxa do BCE + 8 pontos (cerca de 12% em 2026)</li>
    <li><strong>Transações B2G</strong>: mesma taxa, mas prazo de pagamento máximo de 30 dias</li>
    <li><strong>Indemnização fixa</strong>: 40 EUR para despesas de cobrança (sem necessidade de comprovativo)</li>
</ul>

<h2>Etapa 4: A cobrança coerciva (D+60)</h2>

<p>Se todas as cobranças falharem, várias opções estão disponíveis:</p>

<ul>
    <li><strong>Sociedade de cobrança</strong>: encarrega-se dos trâmites mediante uma comissão (15-25%)</li>
    <li><strong>Injunção de pagamento</strong>: procedimento simplificado perante o juiz de paz (para créditos < 15 000 EUR)</li>
    <li><strong>Citação para tribunal</strong>: para os montantes mais elevados</li>
    <li><strong>Mediação comercial</strong>: solução alternativa, mais rápida e menos dispendiosa</li>
</ul>

<h2>Boas práticas para evitar incumprimentos</h2>

<ul>
    <li><strong>Fature rapidamente</strong>: quanto mais espera, maior o risco de incumprimento</li>
    <li><strong>Condições claras</strong>: mencione os prazos e modalidades de pagamento em cada fatura</li>
    <li><strong>Adiantamento</strong>: peça um adiantamento de 30-50% para grandes prestações</li>
    <li><strong>Cobranças automáticas</strong>: utilize um software como o faktur.lu para nunca esquecer de cobrar</li>
    <li><strong>Verifique a solvabilidade</strong>: para os novos clientes, consulte o RCS Luxemburgo</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/delais-paiement-luxembourg-cadre-legal-2026" class="text-primary-500 hover:text-primary-600 text-sm">prazos de pagamento legais →</a></li><li><a href="/pt/blog/automatiser-facturation-7-conseils-gagner-temps" class="text-primary-500 hover:text-primary-600 text-sm">automatizar →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatize as suas cobranças com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">O faktur.lu deteta automaticamente as faturas em atraso e envia cobranças por email. Não deixe mais nenhuma fatura por pagar cair no esquecimento.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'relancer-client-impaye-luxembourg',
            1,
            '2026-04-12 08:31:02',
            'https://images.unsplash.com/photo-1423666639041-f56000c27a9a?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article41(): array
    {
        $title = 'Prazos de pagamento no Luxemburgo: quadro legal 2026';
        $excerpt = 'Descubra os prazos de pagamento legais no Luxemburgo em 2026: regras B2B e B2G, juros de mora, indemnizações e recursos em caso de atraso.';
        $metaTitle = 'Prazos de Pagamento Luxemburgo: Quadro Legal 2026 | Guia';
        $metaDescription = 'Quais são os prazos de pagamento legais no Luxemburgo? B2B, B2G, juros de mora. Todo o quadro legal explicado de forma simples.';
        $content = <<<'HTML'
<p class="lead">No Luxemburgo, os prazos de pagamento são regulados pela lei. Quer fature a uma empresa ou a uma administração, eis as regras a conhecer para proteger a sua tesouraria.</p>

<h2>Prazos legais de pagamento</h2>

<h3>Transações B2B (entre empresas)</h3>

<p>A <strong>lei de 18 de abril de 2004</strong> relativa aos prazos de pagamento nas transações comerciais fixa as seguintes regras:</p>

<ul>
    <li><strong>Prazo por defeito</strong>: 30 dias a contar da receção da fatura</li>
    <li><strong>Prazo máximo contratual</strong>: 60 dias (salvo acordo específico)</li>
    <li>As partes podem acordar um prazo <strong>superior a 60 dias</strong> apenas se isso não constituir um abuso manifesto</li>
</ul>

<h3>Transações B2G (com o setor público)</h3>

<p>Os prazos são mais estritos para as administrações:</p>

<ul>
    <li><strong>Prazo máximo</strong>: 30 dias a contar da receção da fatura</li>
    <li>Este prazo <strong>não pode ser alargado</strong> contratualmente</li>
    <li>O procedimento de verificação não pode exceder <strong>30 dias suplementares</strong></li>
</ul>

<h2>A partir de quando começa a contar o prazo?</h2>

<p>O prazo de pagamento começa a contar a partir de:</p>

<ul>
    <li>A <strong>data de receção da fatura</strong> pelo devedor</li>
    <li>Ou a <strong>data de receção dos bens/serviços</strong>, se a fatura tiver sido enviada antes</li>
    <li>Ou a <strong>data de verificação</strong>, se um processo de verificação estiver previsto contratualmente</li>
</ul>

<p><strong>Conselho:</strong> mencione sempre a <strong>data de vencimento</strong> claramente na sua fatura. Com o faktur.lu, a data de vencimento é calculada automaticamente (30 dias por defeito, personalizável).</p>

<h2>Juros de mora</h2>

<p>Em caso de atraso de pagamento, os juros são devidos <strong>automaticamente</strong>, sem ser necessário enviar uma interpelação formal:</p>

<ul>
    <li><strong>Taxa</strong>: taxa diretora do BCE + 8 pontos percentuais</li>
    <li><strong>Taxa aplicável em 2026</strong>: cerca de 12,5% anuais</li>
    <li>Os juros começam a contar a partir do dia seguinte à data de vencimento</li>
</ul>

<h2>Indemnização fixa de cobrança</h2>

<p>Para além dos juros de mora, o credor tem direito a uma <strong>indemnização fixa de 40 EUR</strong> para despesas de cobrança. Esta indemnização é devida automaticamente, sem necessidade de comprovativo de despesas.</p>

<p>Se as despesas de cobrança reais excederem 40 EUR, o credor pode reclamar o montante efetivo mediante apresentação de comprovativos.</p>

<h2>Boas práticas nas suas faturas</h2>

<p>Para se proteger em caso de litígio, mencione em cada fatura:</p>

<ul>
    <li>A <strong>data de vencimento</strong> precisa (não apenas "30 dias")</li>
    <li>As <strong>modalidades de pagamento</strong> (transferência, com IBAN)</li>
    <li>A menção dos <strong>juros de mora</strong> aplicáveis</li>
    <li>A menção da <strong>indemnização fixa de 40 EUR</strong></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/relancer-client-impaye-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">cobrar a um cliente →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">menções obrigatórias →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Faça a gestão dos seus prazos de pagamento com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">O faktur.lu calcula automaticamente as datas de vencimento, deteta os atrasos e envia cobranças. Mantenha o controlo da sua tesouraria.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'delais-paiement-luxembourg-cadre-legal-2026',
            2,
            '2026-04-12 08:31:02',
            'https://images.unsplash.com/photo-1501139083538-0139583c060f?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article42(): array
    {
        $title = 'Nota de crédito no Luxemburgo: como a estabelecer corretamente';
        $excerpt = 'A nota de crédito permite corrigir ou anular uma fatura. Descubra como a estabelecer corretamente no Luxemburgo com as menções obrigatórias.';
        $metaTitle = 'Nota de Crédito Luxemburgo: Como a Estabelecer Corretamente';
        $metaDescription = 'Como criar uma nota de crédito no Luxemburgo? Menções obrigatórias, casos de utilização, impacto no IVA. Guia prático com exemplos.';
        $content = <<<'HTML'
<p class="lead">Um erro numa fatura? Um cliente devolve um produto? Deve emitir uma <strong>nota de crédito</strong>. Eis como a estabelecer corretamente no Luxemburgo.</p>

<h2>O que é uma nota de crédito?</h2>

<p>Uma nota de crédito é um documento contabilístico que <strong>anula ou corrige parcialmente uma fatura</strong> já emitida. Ao contrário de uma fatura, os montantes de uma nota de crédito são <strong>negativos</strong>.</p>

<p><strong>Importante:</strong> no Luxemburgo, é <strong>proibido modificar ou eliminar uma fatura</strong> uma vez que esta tenha sido finalizada (princípio da imutabilidade). A única forma de corrigir um erro é emitir uma nota de crédito.</p>

<h2>Quando emitir uma nota de crédito?</h2>

<ul>
    <li><strong>Erro de faturação</strong>: montante incorreto, IVA errado, cliente errado</li>
    <li><strong>Devolução de mercadoria</strong>: o cliente devolve a totalidade ou parte da encomenda</li>
    <li><strong>Desconto comercial</strong>: acordo sobre um desconto após a faturação</li>
    <li><strong>Anulação de prestação</strong>: o serviço não foi prestado</li>
    <li><strong>Insolvência do cliente</strong>: crédito incobrável</li>
</ul>

<h2>Menções obrigatórias</h2>

<p>Uma nota de crédito no Luxemburgo deve conter as mesmas menções que uma fatura, mais:</p>

<ul>
    <li>A menção <strong>"Nota de crédito"</strong> claramente visível</li>
    <li>A <strong>referência à fatura de origem</strong> (número e data)</li>
    <li>O <strong>motivo</strong> da nota de crédito</li>
    <li>Os montantes em <strong>negativo</strong> (ou com a menção "a deduzir")</li>
    <li>A discriminação do IVA (mesmas taxas que a fatura de origem)</li>
    <li>Uma <strong>numeração específica</strong> (ex: NC-2026-001)</li>
</ul>

<h2>Impacto no IVA</h2>

<p>A nota de crédito tem um impacto direto na sua declaração de IVA:</p>

<ul>
    <li>O IVA da nota de crédito vem em <strong>dedução do IVA cobrado</strong></li>
    <li>Deve ser declarada no <strong>mesmo período</strong> em que é emitida</li>
    <li>Se o cliente já tiver deduzido o IVA, terá de <strong>regularizar a sua própria declaração</strong></li>
</ul>

<h2>Criar uma nota de crédito com o faktur.lu</h2>

<p>Com o faktur.lu, a criação de uma nota de crédito é simples e segura:</p>

<ol>
    <li>Abra a fatura de origem</li>
    <li>Clique em <strong>"Criar uma nota de crédito"</strong></li>
    <li>Selecione o motivo</li>
    <li>Escolha uma anulação total ou parcial</li>
    <li>A nota de crédito é gerada automaticamente com todas as menções exigidas</li>
</ol>

<p>A referência à fatura de origem é adicionada automaticamente, e os montantes são calculados em negativo. O seu livro de receitas e a sua exportação FAIA são atualizados instantaneamente.</p>

<h2>Diferença entre nota de crédito e nota de débito</h2>

<p>Na prática, no Luxemburgo, o termo oficial utilizado pela administração é "nota de crédito" (Gutschrift em alemão, "avoir" em francês). Em português europeu, fala-se geralmente de <strong>"nota de crédito"</strong>.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">menções obrigatórias →</a></li><li><a href="/pt/blog/guide-complet-facturation-luxembourg-2026" class="text-primary-500 hover:text-primary-600 text-sm">guia de faturação →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Crie as suas notas de crédito com 1 clique</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera automaticamente as suas notas de crédito com todas as menções obrigatórias, ligadas à fatura de origem e conformes à legislação luxemburguesa.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'note-de-credit-luxembourg-comment-etablir',
            1,
            '2026-04-12 08:31:02',
            'https://images.unsplash.com/photo-1586281380349-632531db7ed4?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article43(): array
    {
        $title = 'IVA intracomunitário: guia para empresas luxemburguesas';
        $excerpt = 'Como gerir o IVA intracomunitário no Luxemburgo? Autoliquidação, validação VIES, declarações: tudo o que é preciso saber para faturar na UE.';
        $metaTitle = 'IVA Intracomunitário Luxemburgo: Guia Completo | Regras UE';
        $metaDescription = 'IVA intracomunitário no Luxemburgo: autoliquidação, número de IVA, declaração, casos práticos. Guia completo para faturar na UE.';
        $content = <<<'HTML'
<p class="lead">Fatura clientes noutros países da União Europeia? O <strong>IVA intracomunitário</strong> segue regras específicas que qualquer empreendedor luxemburguês deve dominar. Este guia explica-lhe tudo.</p>

<h2>O que é o IVA intracomunitário?</h2>

<p>O IVA intracomunitário é o regime de IVA que se aplica às trocas de bens e serviços entre empresas situadas em diferentes países da <strong>União Europeia</strong>. O princípio fundamental é a <strong>autoliquidação</strong>: é o comprador, e não o vendedor, que declara e paga o IVA no seu país.</p>

<h2>O número de IVA intracomunitário</h2>

<p>No Luxemburgo, o seu número de IVA intracomunitário tem o formato <strong>LU + 8 dígitos</strong> (exemplo: LU12345678). Este número é:</p>

<ul>
    <li>Atribuído pela <strong>AED</strong> (Administração do registo, dos domínios e do IVA)</li>
    <li>Obrigatório para qualquer transação intracomunitária</li>
    <li>Verificável no <strong>sistema VIES</strong> da Comissão Europeia</li>
</ul>

<p><strong>Conselho:</strong> o faktur.lu verifica automaticamente os números de IVA via VIES quando adiciona um cliente intracomunitário.</p>

<h2>Regras de faturação intracomunitária</h2>

<h3>Venda de serviços B2B (caso mais comum)</h3>

<p>Quando vende um serviço a uma empresa situada noutro país da UE:</p>

<ol>
    <li>Fatura <strong>sem impostos (0% IVA)</strong></li>
    <li>Menciona na fatura: <strong>"Autoliquidação - Artigo 44 da diretiva 2006/112/CE"</strong></li>
    <li>Indica o seu número de IVA <strong>e</strong> o do cliente</li>
    <li>O cliente declara o IVA no seu próprio país (mecanismo de "reverse charge")</li>
</ol>

<h3>Venda de bens B2B</h3>

<p>Para as entregas de bens a uma empresa da UE:</p>

<ol>
    <li>Fatura <strong>sem impostos</strong> (entrega intracomunitária isenta)</li>
    <li>Menciona: <strong>"Entrega intracomunitária isenta - Artigo 138 diretiva 2006/112/CE"</strong></li>
    <li>Deve provar que os bens saíram do Luxemburgo</li>
    <li>A transação deve constar do seu <strong>mapa recapitulativo</strong> (declaração intracomunitária)</li>
</ol>

<h3>Venda a um particular (B2C)</h3>

<p>Atenção, as regras são diferentes para as vendas a particulares na UE:</p>

<ul>
    <li><strong>Serviços eletrónicos</strong>: IVA do país do cliente (regime OSS)</li>
    <li><strong>Vendas à distância de bens</strong>: IVA luxemburguês até ao limiar de 10 000 EUR, depois IVA do país do cliente</li>
    <li><strong>Serviços clássicos</strong>: geralmente IVA luxemburguês</li>
</ul>

<h2>Declarações obrigatórias</h2>

<p>Como empresa luxemburguesa que efetua operações intracomunitárias, deve:</p>

<ul>
    <li><strong>Declaração de IVA periódica</strong>: declarar as suas operações intracomunitárias nos campos apropriados</li>
    <li><strong>Mapa recapitulativo</strong>: declaração mensal ou trimestral listando todas as suas vendas intracomunitárias por cliente e por país</li>
    <li><strong>Intrastat</strong>: declaração estatística para os movimentos de bens que excedem certos limiares</li>
</ul>

<h2>Validação VIES: porque é crucial</h2>

<p>Antes de faturar sem IVA a um cliente da UE, <strong>deve verificar</strong> que o seu número de IVA é válido através do sistema <strong>VIES (VAT Information Exchange System)</strong>. Se o número for inválido:</p>

<ul>
    <li>Deve faturar <strong>com IVA luxemburguês</strong></li>
    <li>Arrisca-se a uma <strong>liquidação adicional</strong> se faturar sem IVA sem verificação</li>
    <li>Conserve uma <strong>prova de verificação VIES</strong> (captura de ecrã ou registo)</li>
</ul>

<p>O faktur.lu verifica automaticamente cada número de IVA intracomunitário e conserva um registo da validação.</p>

<h2>Casos práticos comuns</h2>

<h3>Consultor luxemburguês a faturar a um cliente alemão</h3>
<p>Fatura sem IVA com menção de autoliquidação. O cliente alemão declara o IVA alemão (19%) na sua própria declaração. Declara a operação no seu mapa recapitulativo.</p>

<h3>Agência web luxemburguesa a faturar a um cliente francês</h3>
<p>Mesmo princípio: fatura sem IVA, autoliquidação. O cliente francês declara 20% de IVA francês. Deve verificar o seu número de IVA no VIES antes de faturar.</p>

<h3>E-commerce luxemburguês a vender a um particular belga</h3>
<p>Se as suas vendas B2C na UE excederem 10 000 EUR/ano, deve aplicar o IVA do país do cliente (21% na Bélgica) através do regime <strong>OSS (One-Stop Shop)</strong>.</p>

<h2>Menções obrigatórias na fatura</h2>

<p>Toda a fatura intracomunitária deve mencionar:</p>

<ul>
    <li>O seu número de IVA luxemburguês</li>
    <li>O número de IVA do cliente</li>
    <li>A menção legal de isenção (autoliquidação ou entrega intracomunitária)</li>
    <li>O montante sem IVA e a menção "IVA 0%"</li>
</ul>

<p>O faktur.lu deteta automaticamente o cenário de IVA consoante o país e o tipo de cliente, e aplica as menções legais apropriadas.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">IVA no Luxemburgo →</a></li><li><a href="/pt/blog/facturer-etranger-depuis-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">faturar para o estrangeiro →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fature em toda a UE em conformidade</h3>
    <p class="text-primary-800 mb-4">O faktur.lu deteta automaticamente os cenários de IVA intracomunitário, verifica os números de IVA via VIES e aplica as menções legais corretas.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'tva-intracommunautaire-guide-entreprises-luxembourgeoises',
            2,
            '2026-04-12 08:34:29',
            'https://images.unsplash.com/photo-1519677100203-a0e668c92439?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article44(): array
    {
        $title = 'Excel vs software de faturação: porquê fazer a mudança';
        $excerpt = 'Muitos empreendedores ainda faturam com Excel. Descubra os riscos, os limites e porque é que um software dedicado é indispensável.';
        $metaTitle = 'Excel vs Software de Faturação: Comparação e Migração';
        $metaDescription = 'Ainda fatura com Excel? Descubra porque é que deve passar para um software de faturação e como migrar facilmente.';
        $content = <<<'HTML'
<p class="lead">Ainda utiliza o Excel para fazer as suas faturas? Não está sozinho: <strong>40% dos freelancers e micro-empresas</strong> no Luxemburgo faturam com uma folha de cálculo. Mas esta prática comporta riscos sérios.</p>

<h2>Os limites do Excel para a faturação</h2>

<h3>1. Nenhuma conformidade garantida</h3>

<p>O Excel não verifica se a sua fatura respeita as obrigações legais luxemburguesas. Arrisca-se a esquecer:</p>

<ul>
    <li>A <strong>numeração sequencial</strong> obrigatória (sem falhas nem duplicados)</li>
    <li>As <strong>menções legais</strong> exigidas (IVA, RCS, matrícula)</li>
    <li>A <strong>menção de IVA</strong> correta consoante o cenário (intracomunitário, isenção, etc.)</li>
    <li>O cálculo correto do IVA (erros de arredondamento frequentes)</li>
</ul>

<h3>2. Sem exportação FAIA</h3>

<p>Em caso de controlo fiscal, a AED pode exigir um <strong>ficheiro FAIA</strong> (Ficheiro de Auditoria Informatizado). Impossível de gerar a partir do Excel. Terá de reconstituir manualmente o conjunto da sua contabilidade — um pesadelo.</p>

<h3>3. Risco de erros</h3>

<p>Com o Excel, os erros são frequentes e difíceis de detetar:</p>

<ul>
    <li><strong>Fórmulas partidas</strong>: um copiar-colar infeliz pode falsear todos os seus cálculos</li>
    <li><strong>Duplicados de números</strong>: sem controlo automático, pode atribuir duas vezes o mesmo número</li>
    <li><strong>Esquecimento de linhas</strong>: uma fatura não registada falseia o seu volume de negócios declarado</li>
    <li><strong>Sem cópia de segurança</strong>: um ficheiro corrompido ou eliminado = dados perdidos</li>
</ul>

<h3>4. Perda de tempo</h3>

<p>Com o Excel, cada fatura exige tempo:</p>

<ul>
    <li>Recopiar as informações do cliente em cada fatura</li>
    <li>Calcular o IVA manualmente</li>
    <li>Gerar um PDF, guardá-lo, enviá-lo por email</li>
    <li>Acompanhar os pagamentos noutro ficheiro</li>
    <li>Preparar o livro de receitas no final do mês</li>
</ul>

<p>Um freelancer passa em média <strong>5 horas por mês</strong> na gestão administrativa com Excel. Com um software adaptado, é <strong>menos de uma hora</strong>.</p>

<h2>O que um software de faturação traz</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Funcionalidade</th>
            <th class="border border-gray-300 px-4 py-2 text-center">Excel</th>
            <th class="border border-gray-300 px-4 py-2 text-center">Software</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Numeração automática</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cálculo IVA automático</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Menções legais conformes</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Exportação FAIA</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Envio email integrado</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Acompanhamento de pagamentos</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cobranças automáticas</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Livro de receitas auto</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cópia de segurança automática</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Multi-divisas</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
    </tbody>
</table>

<h2>Como migrar do Excel para um software</h2>

<p>A migração é mais simples do que pensa:</p>

<ol>
    <li><strong>Exporte os seus clientes</strong> a partir do Excel em formato CSV</li>
    <li><strong>Importe-os</strong> para o software (o faktur.lu suporta a importação Excel/CSV com mapeamento de colunas)</li>
    <li><strong>Configure a sua empresa</strong> (nome, IVA, endereço, logótipo)</li>
    <li><strong>Crie a sua primeira fatura</strong>: em 2 minutos, não 15</li>
</ol>

<p>Não precisa de reintroduzir os seus antigos clientes um a um. A <strong>importação Excel/CSV</strong> do faktur.lu deteta automaticamente as colunas e propõe-lhe um mapeamento inteligente.</p>

<h2>Quanto custa?</h2>

<p>Um software de faturação adaptado ao Luxemburgo custa entre <strong>0 e 15 EUR/mês</strong>. É o preço de um café por semana para:</p>

<ul>
    <li>Ganhar mais de 4 horas por mês</li>
    <li>Evitar erros de conformidade</li>
    <li>Estar pronto em caso de controlo fiscal</li>
    <li>Ter a mente tranquila</li>
</ul>

<p>O faktur.lu propõe um <strong>plano gratuito</strong> para começar (3 faturas/mês) e um plano Essencial a 5 EUR/mês para os freelancers.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">escolher o seu software →</a></li><li><a href="/pt/blog/automatiser-facturation-7-conseils-gagner-temps" class="text-primary-500 hover:text-primary-600 text-sm">automatizar a sua faturação →</a></li><li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">exportação FAIA →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Passe do Excel ao faktur.lu em 5 minutos</h3>
    <p class="text-primary-800 mb-4">Importe os seus clientes a partir do Excel, crie a sua primeira fatura conforme e exporte o seu FAIA. Gratuito para começar.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'excel-vs-logiciel-facturation-pourquoi-switch',
            1,
            '2026-04-12 08:34:29',
            'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article45(): array
    {
        $title = 'Factur-X / ZUGFeRD: a faturação eletrónica europeia explicada';
        $excerpt = 'Factur-X (ZUGFeRD) é o standard franco-alemão de faturação eletrónica. Descubra como funciona e porque vai tornar-se incontornável.';
        $metaTitle = 'Factur-X / ZUGFeRD: Guia Faturação Eletrónica Europeia';
        $metaDescription = 'O que é Factur-X (ZUGFeRD)? Como funciona a fatura híbrida PDF + XML? Quando será obrigatória? Guia completo.';
        $content = <<<'HTML'
<p class="lead"><strong>Factur-X</strong> (chamado <strong>ZUGFeRD</strong> na Alemanha) é o standard de faturação eletrónica adotado pela França e pela Alemanha, e em adoção em toda a União Europeia. Eis tudo o que precisa de saber.</p>

<h2>O que é Factur-X?</h2>

<p>Factur-X é um formato de fatura <strong>"híbrido"</strong> que combina dois elementos num único ficheiro PDF:</p>

<ul>
    <li>A <strong>parte visual</strong>: o PDF legível que vê no ecrã e pode imprimir</li>
    <li>A <strong>parte estruturada</strong>: um ficheiro XML integrado no PDF, contendo os dados da fatura num formato normalizado</li>
</ul>

<p>Este duplo formato permite simultaneamente aos <strong>humanos</strong> ler a fatura e aos <strong>softwares</strong> integrá-la automaticamente na sua contabilidade.</p>

<h2>Factur-X vs ZUGFeRD: qual a diferença?</h2>

<p>Nenhuma diferença técnica. Trata-se do <strong>mesmo standard</strong>:</p>

<ul>
    <li><strong>Factur-X</strong> é o nome utilizado em França e nos países francófonos</li>
    <li><strong>ZUGFeRD</strong> (Zentraler User Guide des Forums elektronische Rechnung Deutschland) é o nome utilizado na Alemanha</li>
    <li>Ambos são baseados na norma europeia <strong>EN 16931</strong></li>
</ul>

<h2>Os perfis Factur-X</h2>

<p>O Factur-X propõe vários níveis de detalhe:</p>

<ul>
    <li><strong>Minimum</strong>: informações de base (remetente, destinatário, montante total)</li>
    <li><strong>Basic WL</strong>: sem linhas de detalhe, mas com IVA discriminado</li>
    <li><strong>Basic</strong>: com as linhas de fatura detalhadas</li>
    <li><strong>EN 16931</strong>: perfil completo conforme à norma europeia (recomendado)</li>
    <li><strong>Extended</strong>: dados suplementares para necessidades específicas</li>
</ul>

<p>O faktur.lu gera faturas Factur-X no perfil <strong>EN 16931</strong>, o mais amplamente aceite.</p>

<h2>Porque é que Factur-X é importante</h2>

<h3>Para a sua empresa</h3>
<ul>
    <li><strong>Tratamento automatizado</strong>: os seus clientes podem importar as suas faturas para a sua contabilidade sem reintrodução</li>
    <li><strong>Redução dos erros</strong>: os dados estruturados eliminam os erros de introdução manual</li>
    <li><strong>Pagamento mais rápido</strong>: um tratamento automatizado = prazo de pagamento reduzido</li>
    <li><strong>Imagem profissional</strong>: mostra que a sua empresa está na vanguarda</li>
</ul>

<h3>Para a União Europeia</h3>
<ul>
    <li><strong>Luta contra a fraude do IVA</strong>: os dados estruturados permitem controlos automatizados</li>
    <li><strong>Harmonização</strong>: um único formato para todos os países da UE</li>
    <li><strong>Diretiva ViDA</strong>: a faturação eletrónica tornar-se-á obrigatória para o B2B intracomunitário a partir de 2028-2030</li>
</ul>

<h2>É obrigatório no Luxemburgo?</h2>

<p>Em 2026, Factur-X <strong>ainda não é obrigatório</strong> para o B2B no Luxemburgo. No entanto:</p>

<ul>
    <li>A <strong>França</strong> torna a faturação eletrónica obrigatória progressivamente (2026-2027)</li>
    <li>A <strong>Alemanha</strong> adotou ZUGFeRD como standard de referência</li>
    <li>A <strong>diretiva ViDA</strong> da UE prevê uma generalização a partir de 2028</li>
    <li>O <strong>setor público</strong> luxemburguês utiliza já o Peppol, baseado em standards similares</li>
</ul>

<p><strong>Antecipe:</strong> ao adotar Factur-X agora, está pronto para as futuras obrigações e facilita a vida aos seus clientes que já utilizam este formato.</p>

<h2>Gerar faturas Factur-X com o faktur.lu</h2>

<p>O plano Pro do faktur.lu inclui a geração automática de faturas Factur-X:</p>

<ol>
    <li>Crie a sua fatura normalmente</li>
    <li>Finalize e envie</li>
    <li>O PDF gerado contém automaticamente os dados XML Factur-X integrados</li>
    <li>O seu cliente pode importar a fatura para o seu software contabilístico com um clique</li>
</ol>

<p>Nenhuma ação suplementar da sua parte: é 100% automático.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/peppol-b2g-luxembourg-guide-complet-2026" class="text-primary-500 hover:text-primary-600 text-sm">Peppol →</a></li><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">software de faturação →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Gere faturas Factur-X automaticamente</h3>
    <p class="text-primary-800 mb-4">O faktur.lu integra o formato Factur-X / ZUGFeRD em todas as suas faturas Pro. Antecipe as futuras obrigações europeias.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'factur-x-zugferd-facturation-electronique-europeenne',
            2,
            '2026-04-12 08:34:29',
            'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article46(): array
    {
        $title = 'Como escolher o seu software de faturação no Luxemburgo';
        $excerpt = 'Como escolher o software de faturação certo para a sua empresa luxemburguesa? Eis os critérios essenciais e o nosso comparativo.';
        $metaTitle = 'Software de Faturação Luxemburgo: Como Escolher | Comparativo 2026';
        $metaDescription = 'Como escolher o software de faturação certo no Luxemburgo? Critérios essenciais, conformidade FAIA, preço. Guia comparativo 2026.';
        $content = <<<'HTML'
<p class="lead">Procura um software de faturação adaptado ao Luxemburgo? O mercado propõe inúmeras soluções, mas nem todas estão em conformidade com a legislação luxemburguesa. Eis os <strong>critérios essenciais</strong> para fazer a escolha certa.</p>

<h2>Critério 1: Conformidade luxemburguesa</h2>

<p>É o critério mais importante. Um software adaptado ao Luxemburgo <strong>deve</strong> propor:</p>

<ul>
    <li><strong>Exportação FAIA</strong>: o ficheiro de auditoria informatizado exigido pela AED em caso de controlo fiscal. Sem FAIA, está em infração.</li>
    <li><strong>Numeração sequencial</strong>: sem falhas nem duplicados, conforme à lei luxemburguesa</li>
    <li><strong>Menções legais automáticas</strong>: IVA, RCS, matrícula consoante o cenário</li>
    <li><strong>Taxas de IVA luxemburguesas</strong>: 17%, 14%, 8% e 3% com cálculo automático</li>
    <li><strong>Gestão da isenção de IVA</strong>: para as empresas abaixo do limiar de 35 000 EUR</li>
</ul>

<p><strong>Atenção:</strong> a maior parte dos softwares internacionais (QuickBooks, FreshBooks, Wave) não suporta a exportação FAIA nem as especificidades luxemburguesas.</p>

<h2>Critério 2: Funcionalidades essenciais</h2>

<p>Para além da conformidade, um bom software de faturação deve oferecer:</p>

<ul>
    <li><strong>Faturas e orçamentos</strong> com conversão num clique</li>
    <li><strong>Notas de crédito</strong> ligadas às faturas de origem</li>
    <li><strong>Gestão dos clientes</strong> com histórico de faturação</li>
    <li><strong>Multi-divisas</strong> (EUR, USD, CHF, GBP)</li>
    <li><strong>Envio por email</strong> integrado com acompanhamento de abertura</li>
    <li><strong>Cobranças automáticas</strong> para as faturas em atraso</li>
    <li><strong>Exportações contabilísticas</strong> (Sage BOB, FID-Manager, CSV)</li>
    <li><strong>Livro de receitas</strong> gerado automaticamente</li>
</ul>

<h2>Critério 3: Simplicidade de utilização</h2>

<p>Você é empreendedor, não contabilista. O software deve ser:</p>

<ul>
    <li><strong>Intuitivo</strong>: criar uma fatura em menos de 2 minutos</li>
    <li><strong>Acessível online</strong>: sem instalação, acessível de qualquer lugar</li>
    <li><strong>Mobile-friendly</strong>: poder criar uma fatura a partir do seu telemóvel</li>
    <li><strong>Em português</strong>: interface e suporte na sua língua</li>
</ul>

<h2>Critério 4: Preço adaptado</h2>

<p>Os preços variam enormemente consoante as soluções:</p>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Gama</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Preço mensal</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Público-alvo</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Gratuito</td><td class="border border-gray-300 px-4 py-2">0 EUR</td><td class="border border-gray-300 px-4 py-2">Teste / micro-atividade</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Essencial</td><td class="border border-gray-300 px-4 py-2">5 - 15 EUR</td><td class="border border-gray-300 px-4 py-2">Freelancers / trabalhadores independentes</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Pro</td><td class="border border-gray-300 px-4 py-2">15 - 30 EUR</td><td class="border border-gray-300 px-4 py-2">PME / equipas</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Empresa</td><td class="border border-gray-300 px-4 py-2">50+ EUR</td><td class="border border-gray-300 px-4 py-2">Grandes estruturas</td></tr>
    </tbody>
</table>

<p><strong>O nosso conselho:</strong> evite as soluções demasiado baratas que não incluem o FAIA, e as soluções demasiado caras se for freelancer. Uma boa relação qualidade/preço situa-se entre 5 e 15 EUR/mês.</p>

<h2>Critério 5: Segurança e RGPD</h2>

<ul>
    <li><strong>Alojamento na Europa</strong>: os seus dados devem permanecer na UE (RGPD)</li>
    <li><strong>Cópias de segurança automáticas</strong>: sem perda de dados</li>
    <li><strong>Autenticação 2FA</strong>: segurança reforçada para a sua conta</li>
    <li><strong>Cifragem</strong>: dados cifrados em trânsito e em repouso</li>
</ul>

<h2>Critério 6: Integração contabilística</h2>

<p>Se trabalha com uma sociedade fiduciária luxemburguesa, verifique se o software propõe:</p>

<ul>
    <li><strong>Exportação Sage BOB</strong>: o software mais utilizado pelas fiduciárias no Luxemburgo</li>
    <li><strong>Exportação FID-Manager</strong>: outro software comum</li>
    <li><strong>Portal contabilístico</strong>: acesso só de leitura para o seu contabilista</li>
    <li><strong>Exportação CSV/Excel</strong>: formato universal em último recurso</li>
</ul>

<h2>Porquê o faktur.lu?</h2>

<p>O faktur.lu foi concebido <strong>especificamente para o Luxemburgo</strong>. Responde a todos os critérios acima:</p>

<ul>
    <li>Exportação FAIA nativa, conforme ao formato XML 2.01 da AED</li>
    <li>IVA luxemburguês com gestão da isenção</li>
    <li>Peppol B2G integrado para o setor público</li>
    <li>Exportações Sage BOB e FID-Manager</li>
    <li>Alojado na Europa (em conformidade com o RGPD)</li>
    <li>Interface em 4 línguas (FR, EN, DE, LB)</li>
    <li>Plano gratuito para começar</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/excel-vs-logiciel-facturation-pourquoi-switch" class="text-primary-500 hover:text-primary-600 text-sm">Excel vs software →</a></li><li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">exportação FAIA →</a></li><li><a href="/pt/blog/peppol-b2g-luxembourg-guide-complet-2026" class="text-primary-500 hover:text-primary-600 text-sm">Peppol →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">O software de faturação feito para o Luxemburgo</h3>
    <p class="text-primary-800 mb-4">O faktur.lu é o único software de faturação concebido 100% para o mercado luxemburguês. FAIA, Peppol, IVA, multi-línguas: tudo está integrado.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'choisir-logiciel-facturation-luxembourg-comparatif',
            1,
            '2026-04-12 08:34:29',
            'https://images.unsplash.com/photo-1553877522-43269d4ea984?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article47(): array
    {
        $title = '5 erros frequentes numa fatura de freelancer no Luxemburgo';
        $excerpt = 'Numeração incorreta, IVA esquecido, menções em falta... Descubra os 5 erros mais frequentes nas faturas de freelancer e como evitá-los.';
        $metaTitle = '5 Erros Frequentes numa Fatura de Freelancer Luxemburgo';
        $metaDescription = 'Evite estes 5 erros comuns nas suas faturas de freelancer no Luxemburgo: numeração, menções legais, IVA, prazos. Conselhos práticos.';
        $content = <<<'HTML'
<p class="lead">Como freelancer no Luxemburgo, as suas faturas são a sua montra profissional e um documento legal. No entanto, <strong>mais de 60% das faturas de freelancer contêm pelo menos um erro</strong>. Eis os 5 mais frequentes e como evitá-los.</p>

<h2>Erro 1: Numeração não sequencial</h2>

<p>A lei luxemburguesa exige uma <strong>numeração estritamente sequencial</strong> das suas faturas. Isto significa:</p>

<ul>
    <li><strong>Sem falhas</strong>: se a sua última fatura é F-2026-042, a próxima deve ser F-2026-043</li>
    <li><strong>Sem duplicados</strong>: duas faturas não podem ter o mesmo número</li>
    <li><strong>Sem modificação</strong>: não pode alterar um número após a emissão</li>
</ul>

<p><strong>Porque é grave?</strong> Durante um controlo fiscal, uma numeração incoerente leva a supor que faturas foram eliminadas ou dissimuladas. É um <strong>red flag</strong> para a AED.</p>

<p><strong>Solução:</strong> utilize um software que gera automaticamente os números. Com o faktur.lu, a numeração é sequencial, sem falhas e imutável.</p>

<h2>Erro 2: Menções legais em falta</h2>

<p>Uma fatura no Luxemburgo deve obrigatoriamente conter:</p>

<ul>
    <li>O <strong>nome e o endereço</strong> da sua empresa</li>
    <li>O seu <strong>número de IVA</strong> (ou a menção de isenção)</li>
    <li>O seu <strong>número RCS</strong> ou matrícula</li>
    <li>O nome e o endereço do <strong>cliente</strong></li>
    <li>O <strong>número da fatura</strong></li>
    <li>A <strong>data de emissão</strong></li>
    <li>A <strong>descrição detalhada</strong> da prestação</li>
    <li>O <strong>montante sem IVA, a taxa de IVA e o montante com IVA</strong></li>
</ul>

<p><strong>O erro clássico:</strong> esquecer o número de IVA do cliente nas faturas intracomunitárias, ou não mencionar o motivo de isenção quando o IVA é a 0%.</p>

<p><strong>Solução:</strong> o faktur.lu adiciona automaticamente todas as menções obrigatórias consoante o tipo de cliente e o cenário de IVA.</p>

<h2>Erro 3: Taxa de IVA errada</h2>

<p>No Luxemburgo, existem <strong>4 taxas de IVA</strong>:</p>

<ul>
    <li><strong>17%</strong>: taxa normal (a maioria das prestações de serviços)</li>
    <li><strong>14%</strong>: taxa intermédia (certos serviços específicos)</li>
    <li><strong>8%</strong>: taxa reduzida (eletricidade, gás, cabeleireiro...)</li>
    <li><strong>3%</strong>: taxa super-reduzida (alimentação, livros, imprensa)</li>
</ul>

<p><strong>Os erros comuns:</strong></p>

<ul>
    <li>Aplicar 20% (taxa francesa) em vez de 17%</li>
    <li>Faturar com IVA a um cliente intracomunitário (autoliquidação)</li>
    <li>Esquecer o IVA para um cliente luxemburguês B2C</li>
    <li>Não mencionar a isenção de IVA quando se está abaixo do limiar</li>
</ul>

<p><strong>Solução:</strong> o faktur.lu determina automaticamente a taxa correta e a menção correta consoante o país e o tipo de cliente.</p>

<h2>Erro 4: Sem condições de pagamento</h2>

<p>Muitos freelancers esquecem-se de especificar as <strong>modalidades de pagamento</strong> nas suas faturas:</p>

<ul>
    <li><strong>Data de vencimento</strong>: sem vencimento especificado, o prazo legal é de 30 dias, mas o seu cliente pode ignorá-lo</li>
    <li><strong>Meio de pagamento</strong>: indique o seu IBAN para facilitar a transferência</li>
    <li><strong>Penalizações por atraso</strong>: mencione os juros aplicáveis (taxa BCE + 8 pontos)</li>
    <li><strong>Indemnização fixa</strong>: os 40 EUR para despesas de cobrança</li>
</ul>

<p><strong>Porque é importante:</strong> sem condições claras, não tem qualquer base legal para reclamar juros de mora em caso de incumprimento.</p>

<h2>Erro 5: Modificar uma fatura finalizada</h2>

<p>Uma vez enviada uma fatura ao cliente, é <strong>imutável</strong>. Não pode:</p>

<ul>
    <li>Alterar o montante</li>
    <li>Modificar o cliente</li>
    <li>Eliminar a fatura</li>
    <li>Alterar o número</li>
</ul>

<p>Se cometeu um erro, a <strong>única solução legal</strong> é emitir uma <strong>nota de crédito</strong> que anula a fatura errada, depois criar uma nova fatura corrigida.</p>

<p><strong>O erro clássico:</strong> modificar o ficheiro Excel ou Word da fatura e reenviar uma "versão corrigida". Em caso de controlo fiscal, isto pode ser considerado <strong>falsificação</strong>.</p>

<h2>Bónus: a checklist da fatura perfeita</h2>

<p>Antes de enviar cada fatura, verifique:</p>

<ul>
    <li>&#9745; Número sequencial correto</li>
    <li>&#9745; Data de emissão e data de vencimento</li>
    <li>&#9745; Todas as menções legais presentes</li>
    <li>&#9745; Taxa de IVA correta consoante o cenário</li>
    <li>&#9745; IBAN e condições de pagamento</li>
    <li>&#9745; Montantes sem IVA, IVA e com IVA corretos</li>
    <li>&#9745; Descrição clara da prestação</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">menções obrigatórias →</a></li><li><a href="/pt/blog/note-de-credit-luxembourg-comment-etablir" class="text-primary-500 hover:text-primary-600 text-sm">nota de crédito →</a></li><li><a href="/pt/blog/guide-complet-facturation-luxembourg-2026" class="text-primary-500 hover:text-primary-600 text-sm">guia completo →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fature sem erro com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">O faktur.lu verifica automaticamente cada fatura: numeração, menções legais, IVA, imutabilidade. Zero risco de erro.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            '5-erreurs-frequentes-facture-freelance-luxembourg',
            3,
            '2026-04-12 08:44:06',
            'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article48(): array
    {
        $title = 'Como faturar para o estrangeiro a partir do Luxemburgo';
        $excerpt = 'Faturar um cliente em França, na Alemanha ou fora da UE a partir do Luxemburgo? Descubra as regras de IVA e as menções a aplicar segundo cada caso.';
        $metaTitle = 'Faturar para o Estrangeiro a partir do Luxemburgo: Guia IVA e Regras';
        $metaDescription = 'Como faturar um cliente estrangeiro a partir do Luxemburgo? Regras de IVA por zona (UE, fora da UE), menções obrigatórias e armadilhas a evitar.';
        $content = <<<'HTML'
<p class="lead">Está sediado no Luxemburgo e fatura clientes no estrangeiro? As regras de IVA variam consideravelmente segundo a zona geográfica e o tipo de cliente. Eis um guia claro para cada situação.</p>

<h2>Caso 1: Cliente empresa na UE (B2B intracomunitário)</h2>

<p>É o caso mais frequente para os freelancers e PME luxemburguesas. Exemplo: um consultor luxemburguês fatura uma sociedade alemã.</p>

<h3>Regras a aplicar</h3>
<ul>
    <li>Fatura <strong>sem impostos (0% IVA)</strong></li>
    <li>O cliente declara o IVA no seu país (<strong>autoliquidação / reverse charge</strong>)</li>
    <li>Deve verificar o número de IVA do cliente no <strong>VIES</strong></li>
    <li>Menção obrigatória: <em>"Autoliquidação - Artigo 44 da diretiva 2006/112/CE"</em></li>
</ul>

<h3>Documentos necessários</h3>
<ul>
    <li>O seu número de IVA luxemburguês na fatura</li>
    <li>O número de IVA do cliente (verificado no VIES)</li>
    <li>Declaração no <strong>mapa recapitulativo</strong> de IVA mensal/trimestral</li>
</ul>

<h2>Caso 2: Cliente particular na UE (B2C)</h2>

<p>Vende a um particular noutro país da UE. As regras dependem do tipo de prestação:</p>

<h3>Serviços clássicos (consultoria, design, etc.)</h3>
<ul>
    <li>Aplica o <strong>IVA luxemburguês (17%)</strong></li>
    <li>Sem autoliquidação para os particulares</li>
</ul>

<h3>Serviços eletrónicos (SaaS, formações online, etc.)</h3>
<ul>
    <li>Aplica o <strong>IVA do país do cliente</strong></li>
    <li>Através do regime <strong>OSS (One-Stop Shop)</strong>: uma única declaração para todos os países da UE</li>
    <li>Limiar: 10 000 EUR/ano de vendas B2C na UE</li>
</ul>

<h2>Caso 3: Cliente fora da UE (exportação)</h2>

<p>Fatura um cliente na Suíça, nos Estados Unidos, no Reino Unido ou em qualquer outro país fora da UE.</p>

<h3>Serviços</h3>
<ul>
    <li>Fatura <strong>sem impostos (0% IVA)</strong></li>
    <li>Menção: <em>"Prestação de serviços fora do âmbito de aplicação do IVA luxemburguês"</em></li>
    <li>Sem mapa recapitulativo necessário</li>
</ul>

<h3>Bens (exportação)</h3>
<ul>
    <li>Fatura <strong>sem impostos</strong> (exportação isenta)</li>
    <li>Deve conservar a <strong>prova de exportação</strong> (documento alfandegário)</li>
    <li>Menção: <em>"Exportação isenta - Artigo 146 diretiva 2006/112/CE"</em></li>
</ul>

<h2>Caso especial: a Suíça</h2>

<p>A Suíça não está na UE. No entanto, muitos freelancers luxemburgueses faturam a clientes suíços. As regras:</p>

<ul>
    <li>Serviços: fature <strong>sem IVA</strong>, o cliente suíço declara o IVA no âmbito do mecanismo de importação de serviços</li>
    <li>Sem mapa recapitulativo (reservado às trocas intra-UE)</li>
    <li>Fature em <strong>EUR ou CHF</strong> consoante o acordo com o cliente</li>
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
        <tr><td class="border border-gray-300 px-4 py-2">B2B Luxemburgo</td><td class="border border-gray-300 px-4 py-2">17%</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B UE</td><td class="border border-gray-300 px-4 py-2">0% (autoliquidação)</td><td class="border border-gray-300 px-4 py-2">Art. 44 diretiva</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (serviços)</td><td class="border border-gray-300 px-4 py-2">17% LU</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (eletrónica)</td><td class="border border-gray-300 px-4 py-2">IVA país cliente</td><td class="border border-gray-300 px-4 py-2">Regime OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B fora UE</td><td class="border border-gray-300 px-4 py-2">0%</td><td class="border border-gray-300 px-4 py-2">Fora do âmbito</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C fora UE</td><td class="border border-gray-300 px-4 py-2">0%</td><td class="border border-gray-300 px-4 py-2">Fora do âmbito</td></tr>
    </tbody>
</table>

<h2>Divisas e taxas de câmbio</h2>

<p>Pode faturar em <strong>divisas estrangeiras</strong> (USD, CHF, GBP), mas para a sua declaração de IVA, terá de converter em EUR à <strong>taxa de câmbio do dia da fatura</strong> (taxa BCE).</p>

<p>O faktur.lu suporta a faturação <strong>multi-divisas</strong> e conserva a taxa de câmbio utilizada para cada fatura.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/tva-intracommunautaire-guide-entreprises-luxembourgeoises" class="text-primary-500 hover:text-primary-600 text-sm">IVA intracomunitário →</a></li><li><a href="/pt/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">IVA no Luxemburgo →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fature internacionalmente em conformidade</h3>
    <p class="text-primary-800 mb-4">O faktur.lu deteta automaticamente o cenário de IVA consoante o país e o tipo de cliente, e aplica as menções corretas. Multi-divisas incluído.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'facturer-etranger-depuis-luxembourg',
            1,
            '2026-04-12 08:44:06',
            'https://images.unsplash.com/photo-1526778548025-fa2f459cd5c1?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article49(): array
    {
        $title = 'Isenção de IVA no Luxemburgo: limiar, obrigações e passagem ao regime normal';
        $excerpt = 'A isenção de IVA dispensa-o de faturar o IVA abaixo do limiar de 35 000 EUR. Descubra as regras, vantagens e como gerir a passagem ao regime normal.';
        $metaTitle = 'Isenção IVA Luxemburgo: Limiar 35 000 EUR, Obrigações e Passagem';
        $metaDescription = 'Tudo sobre a isenção de IVA no Luxemburgo: limiar de 35 000 EUR, obrigações, vantagens, inconvenientes e como passar ao regime normal.';
        $content = <<<'HTML'
<p class="lead">No Luxemburgo, as pequenas empresas cujo volume de negócios anual não excede <strong>35 000 EUR</strong> podem beneficiar do <strong>regime de isenção de IVA</strong>. Este regime dispensa-as de faturar o IVA. Eis tudo o que é preciso saber.</p>

<h2>O que é a isenção de IVA?</h2>

<p>A isenção de IVA (artigo 57 bis da lei do IVA luxemburguesa) é um <strong>regime simplificado</strong> que permite às pequenas empresas:</p>

<ul>
    <li><strong>Não faturar IVA</strong> aos seus clientes</li>
    <li><strong>Não declarar IVA</strong> periodicamente</li>
    <li><strong>Não produzir uma declaração de IVA anual</strong></li>
</ul>

<p>Em contrapartida, <strong>não pode deduzir o IVA</strong> sobre as suas compras profissionais.</p>

<h2>Condições de elegibilidade</h2>

<ul>
    <li>Volume de negócios anual sem IVA <strong>inferior a 35 000 EUR</strong></li>
    <li>Atividade exercida no Luxemburgo</li>
    <li>Não ter optado pelo regime normal voluntariamente</li>
    <li>Certas atividades estão excluídas (imobiliário, veículos novos)</li>
</ul>

<h2>Menções obrigatórias nas suas faturas</h2>

<p>Mesmo em isenção de IVA, as suas faturas devem conter uma <strong>menção específica</strong>:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    "IVA não aplicável - Artigo 57 bis da lei modificada de 12 de fevereiro de 1979"
</blockquote>

<p>Deve igualmente:</p>
<ul>
    <li><strong>Não mencionar IVA</strong> (sem linha de IVA na fatura)</li>
    <li>Não indicar <strong>taxa de IVA</strong></li>
    <li>Indicar apenas o <strong>montante líquido</strong> (sem distinção sem IVA/com IVA)</li>
</ul>

<h2>Vantagens da isenção</h2>

<ul>
    <li><strong>Simplicidade</strong>: sem declaração de IVA periódica</li>
    <li><strong>Competitividade</strong>: os seus preços B2C são mais baixos (sem IVA a acrescentar)</li>
    <li><strong>Tesouraria</strong>: sem desfasamento entre IVA cobrado e IVA entregue</li>
    <li><strong>Menos burocracia</strong>: administração simplificada</li>
</ul>

<h2>Inconvenientes</h2>

<ul>
    <li><strong>Sem dedução de IVA</strong>: paga o IVA nas suas compras sem o poder recuperar</li>
    <li><strong>Desvantagem B2B</strong>: os seus clientes empresas não podem deduzir IVA das suas faturas</li>
    <li><strong>Imagem</strong>: certos clientes B2B preferem trabalhar com empresas sujeitas a IVA</li>
    <li><strong>Limiar restritivo</strong>: se exceder 35 000 EUR, a passagem é obrigatória</li>
</ul>

<h2>Quando passar ao regime normal?</h2>

<h3>Passagem obrigatória</h3>
<p>Se o seu volume de negócios exceder <strong>35 000 EUR em 12 meses consecutivos</strong>, deve:</p>

<ol>
    <li>Contactar a <strong>AED</strong> nos 15 dias seguintes ao excesso</li>
    <li>Começar a faturar o IVA <strong>imediatamente</strong></li>
    <li>Submeter as suas declarações de IVA periodicamente</li>
</ol>

<h3>Passagem voluntária</h3>
<p>Pode também optar pelo regime normal <strong>voluntariamente</strong>, mesmo abaixo do limiar. É recomendado se:</p>

<ul>
    <li>Os seus clientes são maioritariamente empresas (B2B) que deduzem o IVA</li>
    <li>Tem investimentos importantes (equipamento, veículo) e quer recuperar o IVA</li>
    <li>Está a aproximar-se do limiar e prefere antecipar</li>
</ul>

<h2>Impacto nas suas faturas com o faktur.lu</h2>

<p>O faktur.lu gere os dois regimes:</p>

<ul>
    <li><strong>Isenção de IVA</strong>: as faturas exibem automaticamente a menção de isenção, sem linha de IVA</li>
    <li><strong>Regime normal</strong>: o IVA é calculado automaticamente com a taxa correta</li>
    <li><strong>Alerta de limiar</strong>: o faktur.lu avisa-o quando se aproxima dos 35 000 EUR para antecipar a passagem</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">taxas de IVA →</a></li><li><a href="/pt/blog/freelance-luxembourg-facturer-conformite" class="text-primary-500 hover:text-primary-600 text-sm">freelancer →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Faça a gestão da sua isenção de IVA com toda a tranquilidade</h3>
    <p class="text-primary-800 mb-4">O faktur.lu adapta-se automaticamente ao seu regime de IVA e alerta-o antes de exceder o limiar de 35 000 EUR.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'franchise-tva-luxembourg-seuil-obligations-regime-normal',
            2,
            '2026-04-12 08:44:06',
            'https://images.unsplash.com/photo-1543286386-713bdd548da4?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article50(): array
    {
        $title = '7 conselhos para automatizar a sua faturação e ganhar tempo';
        $excerpt = 'Perde demasiado tempo com a faturação? Eis 7 conselhos concretos para automatizar e ganhar 5 horas por mês.';
        $metaTitle = 'Automatizar a sua Faturação: 7 Conselhos para Ganhar Tempo';
        $metaDescription = 'Ganhe 5h por mês ao automatizar a sua faturação. 7 conselhos práticos: modelos, cobranças, exportações contabilísticas, controlo do tempo.';
        $content = <<<'HTML'
<p class="lead">A faturação demora em média <strong>5 a 8 horas por mês</strong> para um freelancer ou uma pequena empresa. Boa notícia: a maior parte destas tarefas pode ser automatizada. Eis 7 conselhos para o conseguir.</p>

<h2>1. Pré-registe os seus produtos e serviços</h2>

<p>Em vez de redigitar a descrição e o preço em cada fatura, <strong>crie um catálogo</strong> das suas prestações:</p>

<ul>
    <li>Nome do serviço (ex: "Consultoria - tarifa diária")</li>
    <li>Preço unitário</li>
    <li>Taxa de IVA por defeito</li>
    <li>Unidade (hora, dia, valor fixo)</li>
</ul>

<p>Aquando da criação de uma fatura, basta selecionar o serviço na lista. <strong>Ganho: 2-3 minutos por fatura.</strong></p>

<h2>2. Automatize as cobranças de incumprimentos</h2>

<p>Não perca mais tempo a verificar manualmente que faturas estão em atraso. Configure <strong>cobranças automáticas</strong>:</p>

<ul>
    <li><strong>D+7</strong>: primeiro lembrete amigável por email</li>
    <li><strong>D+15</strong>: cobrança formal</li>
    <li><strong>D+30</strong>: última cobrança antes da interpelação formal</li>
</ul>

<p>Com o faktur.lu, as cobranças são enviadas automaticamente. Pode personalizar os prazos e o conteúdo dos emails. <strong>Ganho: 1-2 horas por mês.</strong></p>

<h2>3. Utilize o controlo de tempo integrado</h2>

<p>Se fatura ao tempo despendido, pare de anotar as suas horas em papel ou no Excel:</p>

<ul>
    <li>Inicie um <strong>cronómetro com 1 clique</strong> quando começa a trabalhar</li>
    <li>Associe cada entrada a um <strong>projeto e a um cliente</strong></li>
    <li>No final do mês, <strong>converta as suas horas em fatura</strong> automaticamente</li>
</ul>

<p><strong>Ganho: 30-45 minutos por mês</strong> e zero esquecimento de horas faturáveis.</p>

<h2>4. Converta os seus orçamentos em faturas com 1 clique</h2>

<p>Quando um orçamento é aceite, não recrie a fatura do zero. Clique em <strong>"Converter em fatura"</strong> e todas as informações são retomadas automaticamente:</p>

<ul>
    <li>Cliente, endereço, IVA</li>
    <li>Linhas de detalhe, quantidades, preços</li>
    <li>Notas e condições</li>
</ul>

<p><strong>Ganho: 5-10 minutos por conversão.</strong></p>

<h2>5. Planifique as suas faturas recorrentes</h2>

<p>Fatura o mesmo montante todos os meses (subscrição, valor fixo de manutenção, renda)? Crie uma <strong>fatura recorrente</strong>:</p>

<ul>
    <li>Defina a frequência (mensal, trimestral, anual)</li>
    <li>A fatura é gerada e enviada automaticamente</li>
    <li>O número sequencial é atribuído automaticamente</li>
</ul>

<p><strong>Ganho: total.</strong> Já não tem de pensar nisso.</p>

<h2>6. Transmita os seus dados ao seu contabilista automaticamente</h2>

<p>Não precisa mais de enviar uma pasta Excel ou um zip de PDF à sua sociedade fiduciária:</p>

<ul>
    <li>Dê-lhe um <strong>acesso contabilístico</strong> só de leitura ao seu software</li>
    <li>Ele acede em tempo real às suas faturas, despesas e livro de receitas</li>
    <li>Exporte em <strong>Sage BOB ou FID-Manager</strong> com 1 clique</li>
</ul>

<p>O faktur.lu propõe um portal contabilístico dedicado a partir do plano Essencial. <strong>Ganho: 1-2 horas por mês.</strong></p>

<h2>7. Importe os seus clientes em massa</h2>

<p>Tem uma lista de clientes num ficheiro Excel? Não os reintroduza um a um:</p>

<ul>
    <li>Exporte o seu ficheiro em CSV ou Excel</li>
    <li>Importe-o para o faktur.lu com o <strong>mapeamento automático das colunas</strong></li>
    <li>Verifique a pré-visualização e valide</li>
</ul>

<p><strong>Ganho: várias horas</strong> se tiver mais de 20 clientes.</p>

<h2>Recapitulativo dos ganhos de tempo</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Ação automatizada</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Ganho estimado</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Pré-registo de produtos</td><td class="border border-gray-300 px-4 py-2">30 min/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cobranças automáticas</td><td class="border border-gray-300 px-4 py-2">1-2h/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Controlo do tempo</td><td class="border border-gray-300 px-4 py-2">45 min/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Conversão orçamento → fatura</td><td class="border border-gray-300 px-4 py-2">30 min/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Faturas recorrentes</td><td class="border border-gray-300 px-4 py-2">30 min/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Portal contabilístico</td><td class="border border-gray-300 px-4 py-2">1-2h/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Importação de clientes</td><td class="border border-gray-300 px-4 py-2">Pontual</td></tr>
        <tr class="bg-primary-50"><td class="border border-gray-300 px-4 py-2 font-bold">Total</td><td class="border border-gray-300 px-4 py-2 font-bold">~5h/mês</td></tr>
    </tbody>
</table>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">software de faturação →</a></li><li><a href="/pt/blog/excel-vs-logiciel-facturation-pourquoi-switch" class="text-primary-500 hover:text-primary-600 text-sm">deixar o Excel →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatize a sua faturação com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">Cobranças automáticas, controlo do tempo, conversão de orçamentos, exportação contabilística: tudo está incluído. Ganhe 5 horas por mês.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'automatiser-facturation-7-conseils-gagner-temps',
            1,
            '2026-04-12 08:44:06',
            'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }

    private function article51(): array
    {
        $title = 'Controlo fiscal no Luxemburgo: como se preparar';
        $excerpt = 'Um controlo fiscal pode chegar a qualquer momento. Descubra como se preparar: documentos a conservar, exportação FAIA e boas práticas.';
        $metaTitle = 'Controlo Fiscal Luxemburgo: Como se Preparar | Guia FAIA';
        $metaDescription = 'Como se preparar para um controlo fiscal no Luxemburgo? Documentos exigidos, exportação FAIA, boas práticas. Guia para não entrar em pânico.';
        $content = <<<'HTML'
<p class="lead">Uma carta da <strong>AED (Administração do registo, dos domínios e do IVA)</strong> a anunciar-lhe um controlo fiscal? Sem pânico. Se a sua contabilidade está bem mantida, o controlo decorrerá sem problema. Eis como se preparar.</p>

<h2>Quem pode ser controlado?</h2>

<p>Qualquer empresa sujeita ao IVA no Luxemburgo pode ser objeto de um controlo fiscal. Os controlos são:</p>

<ul>
    <li><strong>Aleatórios</strong>: seleção ao acaso na base dos contribuintes</li>
    <li><strong>Direcionados</strong>: na sequência de incoerências detetadas nas suas declarações</li>
    <li><strong>Setoriais</strong>: controlos por setor de atividade</li>
    <li><strong>A pedido</strong>: na sequência de um pedido importante de reembolso de IVA</li>
</ul>

<p>As <strong>pequenas empresas e freelancers</strong> não estão isentos. A AED controla todos os tamanhos de empresa.</p>

<h2>O ficheiro FAIA: o elemento central</h2>

<p>Durante um controlo, a AED pode exigir o seu <strong>FAIA (Ficheiro de Auditoria Informatizado)</strong>. É um ficheiro XML normalizado que contém:</p>

<ul>
    <li><strong>Informações da empresa</strong>: matrícula, IVA, endereço</li>
    <li><strong>Plano de contas</strong>: lista das contas utilizadas</li>
    <li><strong>Lançamentos contabilísticos</strong>: todas as transações do exercício</li>
    <li><strong>Faturas emitidas</strong>: detalhe de cada fatura de cliente</li>
    <li><strong>Faturas recebidas</strong>: detalhe das faturas de fornecedores</li>
</ul>

<p><strong>Importante:</strong> se não puder fornecer um FAIA conforme, a administração pode proceder a uma <strong>estimativa oficiosa</strong> do seu volume de negócios e do seu IVA. As consequências financeiras podem ser pesadas.</p>

<h2>Documentos a conservar</h2>

<p>Guarde estes documentos durante <strong>10 anos</strong>:</p>

<ul>
    <li><strong>Todas as faturas emitidas</strong> (numeração sequencial, sem falhas)</li>
    <li><strong>Todas as faturas recebidas</strong> (fornecedores, prestadores)</li>
    <li><strong>O livro de receitas</strong></li>
    <li><strong>As declarações de IVA</strong> entregues</li>
    <li><strong>Os extratos bancários</strong></li>
    <li><strong>Os contratos</strong> com os seus clientes</li>
    <li><strong>As provas de entrega</strong> (para os bens exportados)</li>
    <li><strong>As verificações VIES</strong> (para os clientes intracomunitários)</li>
</ul>

<h2>Decurso de um controlo</h2>

<ol>
    <li><strong>Notificação</strong>: a AED informa-o por carta, geralmente 2-4 semanas antes</li>
    <li><strong>Preparação</strong>: reúna os seus documentos, gere o seu FAIA</li>
    <li><strong>Controlo no local</strong>: um inspetor examina os seus documentos, frequentemente em sua casa ou na do seu contabilista</li>
    <li><strong>Perguntas</strong>: o inspetor pode colocar perguntas sobre transações específicas</li>
    <li><strong>Relatório</strong>: a AED redige um relatório com as suas conclusões</li>
    <li><strong>Regularização</strong>: se forem encontrados erros, recebe um aviso de liquidação adicional</li>
</ol>

<h2>Os pontos mais controlados</h2>

<ul>
    <li><strong>Coerência IVA</strong>: o IVA declarado corresponde às faturas emitidas?</li>
    <li><strong>Deduções de IVA</strong>: as faturas de fornecedores estão conformes?</li>
    <li><strong>Operações intracomunitárias</strong>: as isenções estão justificadas (VIES, provas)?</li>
    <li><strong>Numeração das faturas</strong>: sequência contínua, sem falhas</li>
    <li><strong>Notas de crédito</strong>: estão ligadas a faturas existentes?</li>
    <li><strong>Isenção de IVA</strong>: o limiar de 35 000 EUR é respeitado?</li>
</ul>

<h2>Como se preparar com o faktur.lu</h2>

<p>O faktur.lu foi concebido para que esteja <strong>sempre pronto</strong> em caso de controlo:</p>

<ul>
    <li><strong>Exportação FAIA com 1 clique</strong>: ficheiro XML conforme ao formato 2.01 da AED, gerável a qualquer momento</li>
    <li><strong>Numeração imutável</strong>: nenhuma possibilidade de falha ou de duplicado</li>
    <li><strong>Rastreabilidade completa</strong>: cada ação é registada (audit trail)</li>
    <li><strong>Arquivo PDF/A</strong>: faturas arquivadas com impressão digital (plano Pro)</li>
    <li><strong>Livro de receitas automático</strong>: exportável em PDF ou CSV</li>
    <li><strong>Verificação VIES</strong>: registo de validação dos números de IVA intracomunitários</li>
</ul>

<h2>Conselhos para um controlo sereno</h2>

<ul>
    <li><strong>Seja organizado</strong>: classifique os seus documentos por ano e por tipo</li>
    <li><strong>Seja cooperativo</strong>: responda claramente às perguntas do inspetor</li>
    <li><strong>Faça-se acompanhar</strong>: o seu contabilista pode estar presente durante o controlo</li>
    <li><strong>Antecipe</strong>: gere um FAIA de teste todos os trimestres para verificar a coerência</li>
    <li><strong>Corrija os seus erros</strong>: um erro corrigido espontaneamente antes do controlo é visto mais favoravelmente</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">exportação FAIA →</a></li><li><a href="/pt/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">arquivo →</a></li><li><a href="/pt/blog/livre-des-recettes-luxembourg-obligations-modele" class="text-primary-500 hover:text-primary-600 text-sm">livro de receitas →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Esteja pronto para o controlo fiscal</h3>
    <p class="text-primary-800 mb-4">Com o faktur.lu, o seu FAIA está sempre pronto, as suas faturas estão conformes e a sua rastreabilidade é completa. Fature com a mente tranquila.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente 14 dias</a>
</div>
HTML;
        return $this->base(
            'controle-fiscal-luxembourg-comment-preparer',
            2,
            '2026-04-12 08:44:06',
            'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200&h=630&fit=crop',
            1,
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $content
        );
    }
}

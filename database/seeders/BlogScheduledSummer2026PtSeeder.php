<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Traductions PORTUGAISES des articles programmés juillet-août 2026.
 * translation_key = slug FR ; slug = slug portugais localisé ; locale='pt'.
 * Idempotent (skip si slug existe). published_at = même lundi que le FR.
 */
class BlogScheduledSummer2026PtSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->orderBy('id')->first()?->id ?? 1;

        // Résout l'ID de catégorie par SLUG (robuste : les IDs peuvent différer
        // d'une base à l'autre ; fallback sur 'guides' si une catégorie manque).
        $catMap = [];
        foreach ([1 => 'guides', 2 => 'reglementation', 3 => 'freelances', 4 => 'actualites', 5 => 'guide-creation-entreprise'] as $oldId => $slug) {
            $catMap[$oldId] = \App\Models\BlogCategory::where('slug', $slug)->value('id') ?? 1;
        }

        foreach ($this->articles() as $article) {
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                $this->command?->info("Skip PT: {$article['slug']}");
                continue;
            }

            DB::table('blog_posts')->insert([
                'author_id' => $authorId,
                'category_id' => $catMap[$article['category_id']] ?? 1,
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
            // ===== 2026-07-06 =====
            [
                'category_id' => 1,
                'translation_key' => 'travailler-avec-fiduciaire-luxembourg-guide',
                'slug' => 'trabalhar-com-fiduciaria-luxemburgo-guia',
                'cover_image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&h=630&fit=crop',
                'title' => 'Trabalhar com uma fiduciária no Luxemburgo: o guia prático',
                'meta_title' => 'Trabalhar com uma fiduciária no Luxemburgo | faktur.lu',
                'meta_description' => 'Quando recorrer a uma fiduciária no Luxemburgo, como escolhê-la e como lhe enviar dados limpos para poupar tempo (e dinheiro). Guia prático 2026.',
                'excerpt' => 'Uma boa fiduciária poupa-lhe tempo e evita erros. Desde que a escolha bem e lhe envie dados limpos. Eis como tirar o melhor partido.',
                'published_at' => '2026-07-06 09:00:00',
                'content' => <<<HTML
<p class="lead">No Luxemburgo, a maioria dos independentes e PME delega a contabilidade a uma <strong>fiduciária</strong> (gabinete de contabilidade). Bem escolhida e bem alimentada, poupa-lhe tempo precioso e evita erros dispendiosos. Eis como colaborar eficazmente com ela.</p>

<h2>O que é uma fiduciária e para que serve?</h2>
<p>Uma fiduciária trata, no todo ou em parte, das suas obrigações contabilísticas e fiscais: contabilidade, declarações de IVA, balanço anual, declaração de imposto, aconselhamento. No Luxemburgo não é legalmente obrigatória para um independente, mas é vivamente recomendada assim que a sua atividade ultrapassa algumas faturas por mês.</p>

<h2>Quando recorrer a uma fiduciária?</h2>
<ul>
    <li>Ultrapassa o limiar de isenção de IVA e tem de declarar IVA regularmente.</li>
    <li>Cria uma sociedade (Sàrl, Sàrl-S) com contabilidade organizada obrigatória.</li>
    <li>Falta-lhe tempo ou tranquilidade quanto às obrigações fiscais.</li>
    <li>Quer otimizar (despesas dedutíveis, amortizações) sem correr riscos numa inspeção da AED.</li>
</ul>

<h2>Como escolher a fiduciária certa</h2>
<ul>
    <li><strong>Especialização</strong>: um gabinete habituado a independentes e pequenas empresas compreendê-lo-á melhor do que um orientado para grandes grupos.</li>
    <li><strong>Capacidade de resposta</strong>: faça uma pergunta antes de assinar e veja o prazo de resposta.</li>
    <li><strong>Ferramentas</strong>: uma fiduciária que aceita dados digitais (exportações contabilísticas, FAIA) em vez de pilhas de PDF poupa tempo aos dois lados.</li>
    <li><strong>Transparência dos honorários</strong>: avença mensal ou ao ato? O que inclui exatamente?</li>
</ul>
<p>A <strong>Ordre des Experts-Comptables (OEC)</strong> mantém um diretório oficial dos profissionais autorizados (oec.lu).</p>

<h2>A verdadeira alavanca: enviar dados limpos</h2>
<p>A principal fonte de tempo perdido (e de custos) do lado da fiduciária é a <strong>reintrodução de dados</strong>: pegar nas suas faturas em PDF ou Excel e voltar a inseri-las no software dela. Quanto mais estruturados os seus dados, menos horas ela fatura e menos erros surgem.</p>
<ul>
    <li>Emita faturas conformes com numeração sequencial contínua.</li>
    <li>Centralize tudo numa única ferramenta em vez de ficheiros dispersos.</li>
    <li>Forneça exportações prontas a integrar: FAIA 2.01, Sage BOB 50, Sage 100 ou CSV.</li>
</ul>

<h2>faktur.lu: um portal gratuito para a sua fiduciária</h2>
<p>Com o faktur.lu, fatura normalmente, e a sua fiduciária acede aos seus dados <strong>apenas em leitura</strong> através de um portal de contabilista dedicado: recupera as suas exportações (FAIA 2.01, Sage, CSV) com um clique - sem o incomodar e sem reintrodução. O portal é <strong>gratuito para o gabinete</strong>. Se a sua fiduciária ainda não usa o faktur.lu, fale-lhe do nosso <a href="/pt/parceiros">programa de parceiros</a>.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fature de forma limpa - o seu contabilista vai agradecer</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera faturas conformes no Luxemburgo e exportações prontas para a sua fiduciária (FAIA, Sage, CSV).</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente</a>
</div>
HTML,
            ],

            // ===== 2026-07-13 =====
            [
                'category_id' => 1,
                'translation_key' => 'faire-devis-professionnel-luxembourg',
                'slug' => 'fazer-orcamento-profissional-luxemburgo',
                'cover_image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1200&h=630&fit=crop',
                'title' => 'Fazer um orçamento profissional no Luxemburgo: menções, validade e conversão',
                'meta_title' => 'Fazer um orçamento profissional no Luxemburgo: guia completo | faktur.lu',
                'meta_description' => 'Como redigir um orçamento profissional no Luxemburgo: menções a incluir, prazo de validade, valor jurídico e conversão em fatura. Modelo e boas práticas 2026.',
                'excerpt' => 'O orçamento vincula tanto o seu cliente como a si. Eis as menções a incluir, o valor jurídico, o prazo de validade e como convertê-lo corretamente em fatura.',
                'published_at' => '2026-07-13 09:00:00',
                'content' => <<<HTML
<p class="lead">Antes da fatura, há muitas vezes o <strong>orçamento</strong>. Bem redigido, enquadra a prestação, tranquiliza o cliente e evita litígios. Eis como fazer um orçamento profissional no Luxemburgo.</p>

<h2>Para que serve um orçamento?</h2>
<p>O orçamento é uma <strong>proposta comercial com valores</strong>: descreve a prestação ou os bens, o preço e as condições. Não é obrigatório no Luxemburgo, mas é fortemente recomendado assim que uma prestação tem alguma importância: protege ambas as partes ao fixar o âmbito e o preço antes de começar.</p>

<h2>As menções a incluir num orçamento</h2>
<ul>
    <li>Os seus dados completos (nome, morada, número de IVA se for sujeito passivo).</li>
    <li>Os dados do cliente.</li>
    <li>Um <strong>número de orçamento</strong> e a data de emissão.</li>
    <li>O detalhe das prestações / produtos, com quantidades e preços unitários.</li>
    <li>O valor <strong>sem IVA (HT)</strong>, o IVA aplicável e o <strong>total com IVA (TTC)</strong>.</li>
    <li>O <strong>prazo de validade</strong> da proposta.</li>
    <li>As condições de pagamento e, se aplicável, o adiantamento pedido.</li>
</ul>
<p>Se estiver no regime de isenção de IVA, indique a menção correspondente em vez do IVA.</p>

<h2>Que valor jurídico?</h2>
<p>Um orçamento <strong>assinado e aceite</strong> pelo cliente tem valor contratual: vincula ambas as partes ao âmbito e ao preço acordados. É a sua melhor proteção em caso de desacordo. Por isso, faça datar e assinar o orçamento (menção «De acordo») antes de começar.</p>

<h2>Prazo de validade</h2>
<p>Indique sempre um prazo de validade (por exemplo, 30 dias). Depois disso, deixa de estar vinculado ao preço proposto - útil se os seus custos mudarem. É também uma alavanca comercial: uma proposta limitada no tempo incentiva a decisão.</p>

<h2>Do orçamento à fatura</h2>
<p>Uma vez o orçamento aceite e a prestação realizada, emite a <strong>fatura</strong>. Para evitar erros e dupla introdução, o melhor é <strong>converter o orçamento em fatura</strong>: as linhas, os valores e os dados são reaproveitados, restando apenas atribuir o número de fatura sequencial.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Orçamento e depois fatura com um clique</h3>
    <p class="text-primary-800 mb-4">Com o faktur.lu, crie os seus orçamentos, faça-os aceitar e converta-os em faturas conformes sem reintrodução.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Criar um orçamento gratuitamente</a>
</div>
HTML,
            ],

            // ===== 2026-07-20 =====
            [
                'category_id' => 1,
                'translation_key' => 'facturation-projet-forfait-regie-luxembourg',
                'slug' => 'faturacao-projeto-preco-fixo-tempo-luxemburgo',
                'cover_image' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=1200&h=630&fit=crop',
                'title' => 'Faturação de projeto no Luxemburgo: preço fixo ou por tempo?',
                'meta_title' => 'Faturação de projeto: preço fixo vs tempo no Luxemburgo | faktur.lu',
                'meta_description' => 'Preço fixo ou faturação por tempo para um projeto no Luxemburgo? Vantagens, riscos, adiantamentos e boas práticas para faturar sem surpresas.',
                'excerpt' => 'Faturar um projeto a preço fixo ou por tempo muda tudo: risco, tesouraria, relação com o cliente. Eis como escolher e faturar corretamente cada modelo.',
                'published_at' => '2026-07-20 09:00:00',
                'content' => <<<HTML
<p class="lead">Ao faturar um projeto - desenvolvimento, design, consultoria, obra - dois modelos opõem-se: o <strong>preço fixo</strong> e a <strong>faturação por tempo</strong>. A escolha certa depende do nível de incerteza e da sua tesouraria.</p>

<h2>O preço fixo: um valor fixo para um âmbito definido</h2>
<p>Anuncia um preço global para um resultado preciso. O cliente conhece o orçamento à partida, o que tranquiliza. Mas o risco é seu: se o projeto descarrilar, absorve o excesso.</p>
<p><strong>Quando usar:</strong> âmbito claro e estável, prestação que domina bem.</p>
<p><strong>Boas práticas:</strong> defina o âmbito com precisão no orçamento, preveja uma cláusula para pedidos fora do âmbito e divida o pagamento (adiantamento + marcos + saldo).</p>

<h2>Por tempo: faturação do tempo despendido</h2>
<p>Fatura as horas ou dias efetivamente trabalhados, a uma tarifa acordada. O risco de derrapagem passa para o cliente, e é pago por todo o tempo investido. Em contrapartida, o cliente não tem um orçamento garantido.</p>
<p><strong>Quando usar:</strong> âmbito impreciso ou evolutivo, missões longas, manutenção.</p>
<p><strong>Boas práticas:</strong> registe o tempo com rigor, forneça um mapa de horas claro e fixe eventualmente um limite tranquilizador.</p>

<h2>Comparação</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Critério</th><th class="text-left p-2 bg-slate-100">Preço fixo</th><th class="text-left p-2 bg-slate-100">Por tempo</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Risco de derrapagem</td><td class="p-2 border-b">Para si</td><td class="p-2 border-b">Para o cliente</td></tr>
        <tr><td class="p-2 border-b">Visibilidade do orçamento</td><td class="p-2 border-b">Elevada</td><td class="p-2 border-b">Baixa</td></tr>
        <tr><td class="p-2 border-b">Ideal para</td><td class="p-2 border-b">Âmbito estável</td><td class="p-2 border-b">Âmbito evolutivo</td></tr>
    </tbody>
</table>

<h2>Os adiantamentos: proteja a sua tesouraria</h2>
<p>Num projeto de várias semanas, não fature tudo no fim. Peça um <strong>adiantamento no arranque</strong> e fature por <strong>marcos</strong>. Isto protege a sua tesouraria e limita o risco de incumprimento.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Preço fixo, tempo, adiantamentos: o faktur.lu trata de tudo</h3>
    <p class="text-primary-800 mb-4">Registo de tempo, faturas de adiantamento e de projeto - conformes e em poucos cliques.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente</a>
</div>
HTML,
            ],

            // ===== 2026-07-27 =====
            [
                'category_id' => 2,
                'translation_key' => 'cotisations-sociales-independant-ccss-luxembourg-2026',
                'slug' => 'contribuicoes-sociais-independente-ccss-luxemburgo-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1554224154-26032ffc0d07?w=1200&h=630&fit=crop',
                'title' => 'Contribuições sociais dos independentes no Luxemburgo (CCSS) em 2026',
                'meta_title' => 'Contribuições sociais independente Luxemburgo (CCSS) 2026 | faktur.lu',
                'meta_description' => 'Quanto paga um independente no Luxemburgo? Taxas CCSS, base de cálculo, base mínima e funcionamento. Guia 2026 para antecipar os seus encargos sociais.',
                'excerpt' => 'Enquanto independente no Luxemburgo, contribui para a CCSS sobre os seus rendimentos. Eis como funciona, as taxas indicativas e como antecipar estes encargos.',
                'published_at' => '2026-07-27 09:00:00',
                'content' => <<<HTML
<p class="lead">Ao lançar-se como independente no Luxemburgo, as <strong>contribuições sociais</strong> são muitas vezes a má surpresa. Financiam a sua proteção social (doença, pensão, dependência) e calculam-se sobre os seus rendimentos. Eis o essencial para antecipar.</p>

<h2>O que é a CCSS?</h2>
<p>O <strong>Centre Commun de la Sécurité Sociale (CCSS)</strong> é o organismo que centraliza a inscrição e a cobrança das contribuições sociais no Luxemburgo. Desde o início da sua atividade independente, tem de se inscrever nele.</p>

<h2>Sobre o que se contribui?</h2>
<p>As contribuições são calculadas sobre o seu <strong>rendimento profissional</strong> (o lucro, não o volume de negócios). No arranque, a inscrição faz-se muitas vezes numa base provisória, depois regularizada de acordo com os rendimentos reais comunicados pela administração fiscal.</p>

<h2>Que taxas? (ordem de grandeza)</h2>
<p>No total, as contribuições de um independente representam <strong>cerca de 25 %</strong> do rendimento, repartidas pelos vários ramos:</p>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Ramo</th><th class="text-left p-2 bg-slate-100">Taxa indicativa</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Seguro de pensão</td><td class="p-2 border-b">~16 %</td></tr>
        <tr><td class="p-2 border-b">Seguro de doença (cuidados + subsídios)</td><td class="p-2 border-b">~6 %</td></tr>
        <tr><td class="p-2 border-b">Seguro de dependência</td><td class="p-2 border-b">~1,4 %</td></tr>
        <tr><td class="p-2 border-b">Seguro de acidentes e saúde no trabalho</td><td class="p-2 border-b">~1 %</td></tr>
    </tbody>
</table>
<p class="text-sm text-slate-500">As taxas exatas evoluem e dependem da sua situação. Consulte sempre a CCSS (ccss.public.lu) ou a sua fiduciária para os valores em vigor.</p>

<h2>A base mínima e máxima</h2>
<p>Existe uma <strong>base mínima</strong>: mesmo com rendimento baixo, contribui sobre uma base mínima (ligada ao salário social mínimo). No outro extremo, as contribuições são limitadas acima de um certo rendimento. Em concreto, um rendimento muito modesto gera ainda assim contribuições mínimas - a antecipar na sua tesouraria.</p>

<h2>Como antecipar bem</h2>
<ul>
    <li><strong>Reserve ~25 %</strong> do seu lucro para as contribuições, além do imposto.</li>
    <li>Conte com uma <strong>regularização</strong>: se os rendimentos aumentarem, segue-se uma cobrança adicional de contribuições.</li>
    <li>Mantenha uma contabilidade clara para conhecer o seu lucro real a qualquer momento.</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A reter</p>
    <p>As contribuições sociais não são imposto: financiam a sua pensão e a sua cobertura de saúde. Mas pesam ~25 % do rendimento: é preciso provisioná-las desde a primeira fatura.</p>
</div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Acompanhe o seu rendimento em tempo real</h3>
    <p class="text-primary-800 mb-4">O faktur.lu acompanha o seu volume de negócios e despesas para o ajudar a provisionar contribuições e imposto sem stress.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente</a>
</div>
HTML,
            ],

            // ===== 2026-08-03 =====
            [
                'category_id' => 3,
                'translation_key' => 'fixer-tjm-freelance-luxembourg',
                'slug' => 'definir-tarifa-diaria-freelancer-luxemburgo',
                'cover_image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop',
                'title' => 'Definir a tarifa diária como freelancer no Luxemburgo',
                'meta_title' => 'Definir a tarifa diária como freelancer no Luxemburgo: método 2026 | faktur.lu',
                'meta_description' => 'Como calcular a sua tarifa diária como freelancer no Luxemburgo: encargos, contribuições, dias não faturáveis e um método simples para não se vender abaixo do valor.',
                'excerpt' => 'Muitos freelancers definem a tarifa a olho e acabam a trabalhar com prejuízo. Eis um método simples para calcular uma tarifa diária que cubra mesmo os seus custos.',
                'published_at' => '2026-08-03 09:00:00',
                'content' => <<<HTML
<p class="lead">A armadilha clássica de quem começa: dividir um antigo salário por 20 dias e fazer disso a tarifa diária. Resultado: trabalha com prejuízo sem dar por isso. Eis como calcular uma <strong>tarifa diária</strong> realista no Luxemburgo.</p>

<h2>A sua tarifa diária não é «salário / 20»</h2>
<p>Um trabalhador por conta de outrem é pago por ~220 dias/ano, férias e doença incluídas, sem encargos a gerir. Um freelancer, esse, não fatura todos os dias e suporta todos os encargos. A sua tarifa tem de cobrir muito mais do que o «salário» pretendido.</p>

<h2>Os 4 pontos a integrar</h2>
<ul>
    <li><strong>O seu rendimento líquido pretendido.</strong> O que quer realmente guardar.</li>
    <li><strong>As contribuições sociais (~25 %).</strong> Veja o nosso artigo sobre as <a href="/pt/blog/contribuicoes-sociais-independente-ccss-luxemburgo-2026">contribuições CCSS</a>.</li>
    <li><strong>O imposto sobre o rendimento.</strong> Progressivo, a provisionar.</li>
    <li><strong>As suas despesas profissionais.</strong> Software, seguro, contabilista, equipamento, formação, deslocações.</li>
</ul>

<h2>Os dias realmente faturáveis</h2>
<p>De ~252 dias úteis, retire as férias, feriados, eventual doença e, sobretudo, o tempo <strong>não faturável</strong>: prospeção, administrativo, orçamentos, formação. Na prática, muitos freelancers faturam <strong>180 a 200 dias por ano</strong>, por vezes menos no primeiro ano.</p>

<h2>O método de cálculo</h2>
<ol>
    <li>Some as suas <strong>necessidades anuais</strong>: rendimento líquido + contribuições + imposto provisionado + despesas profissionais.</li>
    <li>Divida pelo seu <strong>número realista de dias faturáveis</strong>.</li>
    <li>Obtém a sua <strong>tarifa diária mínima</strong> - abaixo dela, perde dinheiro.</li>
</ol>
<p><strong>Exemplo:</strong> se as suas necessidades anuais totais são 90 000 € e fatura 180 dias, a sua tarifa diária mínima é 500 €. Qualquer tarifa inferior significa trabalhar com prejuízo.</p>

<h2>Para além do mínimo: o valor</h2>
<p>A tarifa mínima cobre os seus custos; o seu preço final depende depois do seu <strong>valor</strong>: especialização, raridade, resultados para o cliente. Não fature o seu tempo, fature o que faz o cliente ganhar.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Acompanhe o seu tempo e a sua rentabilidade</h3>
    <p class="text-primary-800 mb-4">Com o faktur.lu, acompanhe os dias faturados, as despesas e o volume de negócios para verificar se a sua tarifa cumpre o prometido.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente</a>
</div>
HTML,
            ],

            // ===== 2026-08-10 =====
            [
                'category_id' => 2,
                'translation_key' => 'tva-deductible-depenses-professionnelles-luxembourg',
                'slug' => 'iva-dedutivel-despesas-profissionais-luxemburgo',
                'cover_image' => 'https://images.unsplash.com/photo-1599658880436-c61792e70672?w=1200&h=630&fit=crop',
                'title' => 'Recuperar o IVA das despesas profissionais no Luxemburgo',
                'meta_title' => 'IVA dedutível no Luxemburgo: recuperar o IVA das suas compras | faktur.lu',
                'meta_description' => 'Como recuperar o IVA das suas despesas profissionais no Luxemburgo: condições, despesas dedutíveis ou não, e funcionamento da declaração. Guia 2026.',
                'excerpt' => 'O IVA que paga nas suas compras profissionais é, em geral, recuperável. Eis as condições, os casos particulares e como funciona na prática.',
                'published_at' => '2026-08-10 09:00:00',
                'content' => <<<HTML
<p class="lead">Quando é sujeito passivo de IVA no Luxemburgo, fatura IVA aos seus clientes (IVA liquidado), mas pode também <strong>recuperar o IVA pago nas suas compras profissionais</strong> (IVA dedutível). É um mecanismo central - eis como funciona.</p>

<h2>O princípio: IVA liquidado - IVA dedutível</h2>
<p>Em cada declaração, entrega ao Estado a diferença entre o IVA que <strong>liquidou</strong> nas suas vendas e o IVA <strong>dedutível</strong> nas suas compras. Se investiu muito num período, o IVA dedutível pode até ultrapassar o liquidado: obtém então um crédito de IVA.</p>

<h2>As condições para deduzir o IVA</h2>
<ul>
    <li>É <strong>sujeito passivo</strong> de IVA (não está no regime de isenção).</li>
    <li>A despesa é <strong>profissional</strong> e ligada à sua atividade tributável.</li>
    <li>Possui uma <strong>fatura conforme</strong> com o IVA e o número de IVA do fornecedor.</li>
</ul>

<h2>Despesas geralmente dedutíveis</h2>
<p>Equipamento e consumíveis, software e subscrições profissionais, honorários (contabilista, advogado), renda de instalações profissionais, telecomunicações, formação - desde que sirvam a sua atividade.</p>

<h2>Os casos particulares (dedução limitada ou excluída)</h2>
<p>Algumas despesas são parcial ou totalmente excluídas do direito à dedução, sobretudo quando têm uma componente privada ou de representação. Despesas de uso misto (privado e profissional), certas despesas de receção ou de viatura podem ser limitadas. Em caso de dúvida, confirme junto da <strong>AED</strong> ou da sua fiduciária.</p>
<p class="text-sm text-slate-500">As regras exatas de dedutibilidade constam da lei do IVA luxemburguesa e podem evoluir. Consulte a AED ou um profissional.</p>

<h2>Como funciona na prática</h2>
<p>Regista as suas despesas com o respetivo IVA ao longo do período e transporta o total de IVA dedutível para a sua <strong>declaração de IVA</strong> (mensal, trimestral ou anual conforme o regime). Uma contabilidade de despesas bem organizada evita esquecer IVA recuperável - dinheiro que ficaria por recuperar.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Deixe de perder IVA recuperável</h3>
    <p class="text-primary-800 mb-4">O faktur.lu regista as suas despesas e o respetivo IVA, e prepara dados limpos para a sua declaração e a sua fiduciária.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente</a>
</div>
HTML,
            ],

            // ===== 2026-08-17 =====
            [
                'category_id' => 1,
                'translation_key' => 'facture-acompte-luxembourg-regles-mentions',
                'slug' => 'fatura-adiantamento-luxemburgo-regras',
                'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
                'title' => 'Fatura de adiantamento no Luxemburgo: regras, menções e IVA',
                'meta_title' => 'Fatura de adiantamento no Luxemburgo: regras e menções | faktur.lu',
                'meta_description' => 'Como emitir uma fatura de adiantamento no Luxemburgo: menções obrigatórias, tratamento do IVA e ligação com a fatura final. Guia prático 2026.',
                'excerpt' => 'Pedir um adiantamento protege a sua tesouraria, mas implica uma verdadeira fatura, com as suas regras de IVA. Eis como fazer corretamente.',
                'published_at' => '2026-08-17 09:00:00',
                'content' => <<<HTML
<p class="lead">Pedir um <strong>adiantamento</strong> antes de iniciar um trabalho é uma excelente proteção de tesouraria. Mas um adiantamento não é um simples «pagamento»: dá origem a uma verdadeira <strong>fatura de adiantamento</strong>, com as suas regras. Eis como fazer no Luxemburgo.</p>

<h2>Porquê faturar um adiantamento?</h2>
<p>Numa prestação longa ou num projeto, o adiantamento protege a sua tesouraria, compromete o cliente e reduz o risco de incumprimento. É particularmente útil na <a href="/pt/blog/faturacao-projeto-preco-fixo-tempo-luxemburgo">faturação de projeto</a>.</p>

<h2>As menções de uma fatura de adiantamento</h2>
<p>Uma fatura de adiantamento é uma fatura por inteiro: deve conter as menções obrigatórias habituais (os seus dados e os do cliente, número e data, número de IVA), além de:</p>
<ul>
    <li>A menção clara de que se trata de um <strong>adiantamento</strong> e a que encomenda/orçamento se refere.</li>
    <li>O <strong>valor do adiantamento</strong> sem IVA, o IVA aplicável e o total com IVA.</li>
    <li>Um número sequencial, como qualquer fatura.</li>
</ul>

<h2>O tratamento do IVA</h2>
<p>Ponto importante: numa prestação de serviços, o IVA torna-se em princípio <strong>exigível no recebimento do adiantamento</strong>, pelo valor recebido. Ou seja, declara e entrega o IVA sobre o adiantamento assim que este é recebido, sem esperar pela fatura final.</p>
<p class="text-sm text-slate-500">O momento de exigibilidade do IVA depende da natureza da operação. Em caso de dúvida, confirme junto da AED ou da sua fiduciária.</p>

<h2>A fatura final</h2>
<p>No fim da prestação, emite a <strong>fatura de saldo</strong>. Indica o valor total da prestação e depois <strong>deduz o adiantamento já faturado</strong> (e o respetivo IVA), faturando apenas o restante. Assim, o cliente nunca paga duas vezes o mesmo valor, e o IVA total mantém-se correto.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Em resumo</p>
    <p>Adiantamento = verdadeira fatura, com IVA exigível no recebimento. A fatura final indica o total e deduz o adiantamento já faturado.</p>
</div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Adiantamentos e saldos geridos automaticamente</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera as suas faturas de adiantamento e de saldo conformes, com o tratamento de IVA correto.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente</a>
</div>
HTML,
            ],

            // ===== 2026-08-24 =====
            [
                'category_id' => 2,
                'translation_key' => 'impot-independant-luxembourg-2026',
                'slug' => 'imposto-independentes-luxemburgo-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=1200&h=630&fit=crop',
                'title' => 'Imposto dos independentes no Luxemburgo: como funciona em 2026',
                'meta_title' => 'Imposto dos independentes no Luxemburgo 2026: guia | faktur.lu',
                'meta_description' => 'Como é tributado o independente no Luxemburgo? Lucro, tabela progressiva, pagamentos por conta trimestrais, encargos dedutíveis. Guia 2026 para antecipar o imposto.',
                'excerpt' => 'O imposto do independente não se calcula sobre o volume de negócios mas sobre o lucro, segundo uma tabela progressiva. Eis o essencial para o antecipar bem.',
                'published_at' => '2026-08-24 09:00:00',
                'content' => <<<HTML
<p class="lead">Depois das <a href="/pt/blog/contribuicoes-sociais-independente-ccss-luxemburgo-2026">contribuições sociais</a>, o outro grande encargo do independente é o <strong>imposto sobre o rendimento</strong>. Boa notícia: não é tributado sobre tudo o que recebe. Eis como funciona no Luxemburgo.</p>

<h2>É tributado sobre o lucro, não sobre o volume de negócios</h2>
<p>A base tributável do independente é o <strong>lucro</strong>: o seu volume de negócios menos os seus <strong>encargos profissionais dedutíveis</strong> (despesas, compras, amortizações, contribuições sociais…). Quanto mais rigorosa a sua contabilidade, mais deduz aquilo a que tem direito.</p>

<h2>Uma tabela progressiva</h2>
<p>O imposto sobre o rendimento luxemburguês é <strong>progressivo</strong>: quanto mais elevado o rendimento tributável, maior a taxa marginal, por escalões. Uma parte modesta do rendimento não é tributada, depois as taxas sobem até uma taxa marginal superior elevada. Acresce a <strong>contribuição para o fundo de emprego</strong> (imposto de solidariedade). A sua <strong>classe de imposto</strong> (consoante a situação familiar) influencia o cálculo.</p>
<p class="text-sm text-slate-500">Os escalões e as taxas exatas da tabela são fixados por lei e revistos regularmente. Consulte a administração fiscal (ACD, impotsdirects.public.lu) ou a sua fiduciária para a tabela em vigor.</p>

<h2>Os pagamentos por conta trimestrais</h2>
<p>A ACD pede em geral o pagamento de <strong>pagamentos por conta trimestrais</strong>, estimados com base nos seus rendimentos passados. Na declaração anual, o saldo é regularizado: paga o complemento ou é reembolsado. Se os rendimentos aumentarem, conte com pagamentos por conta mais elevados no ano seguinte.</p>

<h2>A declaração anual</h2>
<p>Todos os anos, preenche a sua declaração de imposto (online através do MyGuichet ou com a sua fiduciária). Aí indica o seu lucro e os elementos dedutíveis. É aqui que contam todos os comprovativos reunidos durante o ano.</p>

<h2>Como antecipar bem</h2>
<ul>
    <li><strong>Provisione o imposto</strong> para além dos ~25 % de contribuições sociais: não gaste tudo o que recebe.</li>
    <li>Guarde todos os <strong>comprovativos de despesas</strong>: cada despesa profissional dedutível reduz a sua base tributável.</li>
    <li>Mantenha a contabilidade atualizada para conhecer o seu lucro real e evitar surpresas na regularização.</li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Acompanhe o seu lucro durante todo o ano</h3>
    <p class="text-primary-800 mb-4">O faktur.lu acompanha receitas e despesas para lhe dar uma visão clara do lucro - e antecipar imposto e contribuições.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente</a>
</div>
HTML,
            ],

            // ===== 2026-08-31 =====
            [
                'category_id' => 5,
                'translation_key' => 'sarl-s-vs-entreprise-individuelle-luxembourg',
                'slug' => 'sarl-s-vs-empresa-individual-luxemburgo',
                'cover_image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200&h=630&fit=crop',
                'title' => 'SARL-S ou empresa individual no Luxemburgo: que estatuto escolher?',
                'meta_title' => 'SARL-S ou empresa individual no Luxemburgo? Comparação | faktur.lu',
                'meta_description' => 'SARL-S ou empresa individual no Luxemburgo: responsabilidade, capital, contabilidade, fiscalidade. Comparação clara para escolher o estatuto certo em 2026.',
                'excerpt' => 'Começar como empresa individual ou criar uma SARL-S? Ambas têm vantagens. Eis uma comparação clara para escolher consoante a sua situação.',
                'published_at' => '2026-08-31 09:00:00',
                'content' => <<<HTML
<p class="lead">Ao lançar-se no Luxemburgo, uma pergunta surge sempre: ficar como <strong>empresa individual</strong> ou criar uma <strong>SARL-S</strong> (sociedade de responsabilidade limitada simplificada)? Eis as verdadeiras diferenças para decidir.</p>

<h2>A empresa individual</h2>
<p>É a forma mais simples: exerce em nome próprio, sem criar uma pessoa coletiva distinta. Sem capital, pouco formalismo.</p>
<p><strong>O ponto-chave:</strong> a sua responsabilidade é <strong>ilimitada</strong> - o seu património pessoal pode ser afetado em caso de dívidas profissionais.</p>

<h2>A SARL-S (sociedade simplificada)</h2>
<p>A SARL-S foi criada para facilitar o empreendedorismo. É reservada a <strong>pessoas singulares</strong>, com um <strong>capital inicial reduzido</strong> (entre 1 € e 12 000 €), e oferece uma <strong>responsabilidade limitada</strong> às entradas: o seu património pessoal está, em princípio, protegido.</p>
<p>Em contrapartida, implica mais formalismo: constituição, contabilidade organizada, obrigações de sociedade, e parte do lucro deve ser afetada a uma reserva legal até atingir o capital de uma SARL clássica.</p>

<h2>Comparação</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Critério</th><th class="text-left p-2 bg-slate-100">Empresa individual</th><th class="text-left p-2 bg-slate-100">SARL-S</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Responsabilidade</td><td class="p-2 border-b">Ilimitada (património pessoal)</td><td class="p-2 border-b">Limitada às entradas</td></tr>
        <tr><td class="p-2 border-b">Capital</td><td class="p-2 border-b">Nenhum</td><td class="p-2 border-b">1 € a 12 000 €</td></tr>
        <tr><td class="p-2 border-b">Formalismo</td><td class="p-2 border-b">Mínimo</td><td class="p-2 border-b">Maior (sociedade)</td></tr>
        <tr><td class="p-2 border-b">Contabilidade</td><td class="p-2 border-b">Simplificada possível</td><td class="p-2 border-b">Organizada</td></tr>
        <tr><td class="p-2 border-b">Fiscalidade</td><td class="p-2 border-b">Imposto sobre o rendimento</td><td class="p-2 border-b">Imposto sobre sociedades</td></tr>
    </tbody>
</table>

<h2>Como escolher?</h2>
<ul>
    <li><strong>Atividade de baixo risco, arranque leve</strong> → a empresa individual chega muitas vezes.</li>
    <li><strong>Risco financeiro, necessidade de proteger o património, imagem de «sociedade»</strong> → a SARL-S é interessante.</li>
</ul>
<p>A escolha tem consequências jurídicas e fiscais duradouras: faça-se acompanhar por uma <a href="/pt/blog/trabalhar-com-fiduciaria-luxemburgo-guia">fiduciária</a> ou pela House of Entrepreneurship antes de decidir.</p>
<p class="text-sm text-slate-500">As condições exatas (capital, reserva legal, autorizações) são fixadas por lei e detalhadas no guichet.lu e junto do Luxembourg Business Registers (LBR).</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Seja qual for o estatuto, fature em conformidade</h3>
    <p class="text-primary-800 mb-4">Empresa individual ou SARL-S, o faktur.lu gera faturas conformes no Luxemburgo e as exportações para o seu contabilista.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente</a>
</div>
HTML,
            ],
        ];
    }
}

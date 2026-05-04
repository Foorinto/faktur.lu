<x-mail::message>
# Bom dia {{ $user->name }},

O seu período de teste gratuito do faktur.lu termina dentro de **{{ $daysRemaining }} {{ $daysRemaining > 1 ? 'dias' : 'dia' }}**.

Durante o período de teste, teve acesso a todas as funcionalidades Pro:
- Clientes e faturas ilimitados
- Exportação FAIA para o controlo fiscal
- Arquivo PDF/A durante 10 anos
- Lembretes automáticos

## Continue a faturar com tranquilidade

Escolha o plano que corresponde às suas necessidades:

**Essencial** - 4€/mês
- 10 clientes, 20 faturas/mês
- Ideal para começar

**Pro** - 9€/mês
- Tudo ilimitado + FAIA + arquivo
- Para freelancers estabelecidos

<x-mail::button :url="$subscriptionUrl" color="primary">
Escolher a minha subscrição
</x-mail::button>

Se tiver alguma questão, não hesite em contactar-nos através do suporte.

Com os melhores cumprimentos,<br>
A equipa faktur.lu
</x-mail::message>

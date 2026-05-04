<x-mail::message>
# Bom dia {{ $user->name }},

O seu período de teste gratuito do faktur.lu terminou.

## A sua conta está em modo de leitura

Pode continuar a iniciar sessão para:
- Consultar as suas faturas existentes
- Transferir os seus documentos
- Aceder aos seus dados

No entanto, já não pode criar novas faturas ou orçamentos.

## Reative a sua conta

Escolha uma subscrição para recuperar o acesso completo:

**Essencial** - 4€/mês
- 10 clientes, 20 faturas/mês

**Pro** - 9€/mês
- Tudo ilimitado + FAIA + arquivo

<x-mail::button :url="$subscriptionUrl" color="primary">
Subscrever agora
</x-mail::button>

Os seus dados são conservados e estão à sua espera. Nenhuma informação é perdida.

Com os melhores cumprimentos,<br>
A equipa faktur.lu
</x-mail::message>

export class PedidoService {
  faturar(pedidoAtual: Pedido) {
    const chave_api = process.env.API_KEY;
    console.log(pedidoAtual);
    try {
      this.enviar(pedidoAtual, chave_api);
    } catch {}
    throw new Error('pedido já faturado');
  }
}

export function buscar(id: string) {
  throw new NotFoundException(id);
}

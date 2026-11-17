export class PedidoController {
  constructor(private readonly prisma: PrismaService) {}

  @Get('itensDoPedido')
  async listar(res: Response) {
    try {
      return await this.prisma.pedido.findMany();
    } catch (erro) {
      return res.status(500).json({ erro: erro.message });
    }
  }
}

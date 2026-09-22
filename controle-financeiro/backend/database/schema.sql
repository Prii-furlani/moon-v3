-- Estrutura de Banco de Dados: Controle Financeiro (MoonFinance)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(60) NOT NULL,
    `sobrenome` VARCHAR(80) NOT NULL,
    `cpf` VARCHAR(14) NOT NULL UNIQUE,
    `data_nascimento` DATE NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    `onboarding_completo` TINYINT(1) NOT NULL DEFAULT 0,
    `avatar` VARCHAR(255) NULL DEFAULT NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_usuarios_email` (`email`),
    INDEX `idx_usuarios_cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `contas`
--

CREATE TABLE IF NOT EXISTS `contas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `saldo_inicial` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `cor` VARCHAR(7) DEFAULT '#000000',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_contas_usuario` (`usuario_id`),
  CONSTRAINT `fk_conta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `categorias`
--

CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `tipo` ENUM('receita','despesa') NOT NULL,
  `cor` VARCHAR(7) DEFAULT '#cccccc',
  `icone` VARCHAR(50) DEFAULT 'Tag',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_categorias_usuario` (`usuario_id`),
  CONSTRAINT `fk_categoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `transacoes`
--

CREATE TABLE IF NOT EXISTS `transacoes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `conta_id` INT UNSIGNED NOT NULL,
  `categoria_id` INT UNSIGNED DEFAULT NULL,
  `tipo` ENUM('receita','despesa','transferencia') NOT NULL,
  `valor` DECIMAL(15,2) NOT NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `data_transacao` DATE NOT NULL,
  `efetivada` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_transacoes_usuario` (`usuario_id`),
  INDEX `idx_transacoes_conta` (`conta_id`),
  INDEX `idx_transacoes_categoria` (`categoria_id`),
  INDEX `idx_transacoes_data` (`data_transacao`),
  CONSTRAINT `fk_transacao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transacao_conta` FOREIGN KEY (`conta_id`) REFERENCES `contas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transacao_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
